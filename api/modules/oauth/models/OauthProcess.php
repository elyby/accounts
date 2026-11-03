<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\components\Tokens\TokenReader;
use common\components\OAuth2\Entities\UserEntity;
use common\components\OAuth2\Events\RequestedRefreshToken;
use common\components\OAuth2\Repositories\ClientRepository;
use common\components\OAuth2\ScopesMapper;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;
use Yii;

final readonly class OauthProcess {

    public function __construct(
        private AuthorizationServer $server,
    ) {
    }

    /**
     * A request that should check the passed OAuth2 authorization params and build a response
     * for our frontend application.
     *
     * The input data is the standard GET parameters list according to the OAuth2 standard:
     * $_GET = [
     *     client_id,
     *     redirect_uri,
     *     response_type,
     *     scope,
     *     state,
     * ];
     *
     * @return array<mixed>
     */
    public function validate(ServerRequestInterface $request): array {
        try {
            $authRequest = $this->server->validateAuthorizationRequest($request);
            $client = $authRequest->getClient();
            /** @var OauthClient $clientModel */
            $clientModel = $this->findClient($client->getIdentifier());
            $response = $this->buildSuccessResponse($request, $clientModel, $authRequest->getScopes());
        } catch (OAuthServerException $e) {
            $response = $this->buildCompleteErrorResponse($e);
        }

        return $response;
    }

    /**
     * This method generates authorization_code and a link
     * for the user's further redirect to the client's site.
     *
     * The input data are the same parameters that were necessary for validation request:
     * $_GET = [
     *     client_id,
     *     redirect_uri,
     *     response_type,
     *     scope,
     *     state,
     * ];
     *
     * Also, the accept field, which shows that the user has clicked on the "Accept" button.
     * If the field is present, it will be interpreted as any value resulting in false positives.
     * Otherwise, the value will be interpreted as "true".
     *
     * @return array<mixed>
     */
    public function complete(ServerRequestInterface $request): array {
        try {
            Yii::$app->statsd->inc('oauth.complete.attempt');

            $authRequest = $this->server->validateAuthorizationRequest($request);
            /** @var Account $account */
            $account = Yii::$app->user->identity->getAccount();
            /** @var OauthClient $client */
            $client = $this->findClient($authRequest->getClient()->getIdentifier());

            $canBeAutoApproved = $this->canBeAutoApproved($account, $client, $authRequest);
            $acceptParam = ((array)$request->getParsedBody())['accept'] ?? null;
            if ($acceptParam === null && !$canBeAutoApproved) {
                Yii::$app->statsd->inc('oauth.complete.approve_required');
                throw $this->createAcceptRequiredException();
            }

            // At this point if the $acceptParam is an empty, then the application can be auto approved
            $approved = $acceptParam === null || in_array($acceptParam, [1, '1', true, 'true'], true);
            if ($approved) {
                $this->storeOauthSession($account, $client, $authRequest);
            }

            $authRequest->setUser(new UserEntity($account->id));
            $authRequest->setAuthorizationApproved($approved);
            $response = $this->server->completeAuthorizationRequest($authRequest, new Response(200));

            $result = [
                'success' => true,
            ];
            if ($response->hasHeader('Location')) {
                $result['redirectUri'] = (string)Uri::withQueryValue(new Uri($response->getHeaderLine('Location')), 'iss', $this->getIssuer());
            }

            Yii::$app->statsd->inc('oauth.complete.success');
        } catch (OAuthServerException $e) {
            if ($e->getErrorType() === 'accept_required') {
                // TODO: revoke access if there previously was an oauth session?
                Yii::$app->statsd->inc('oauth.complete.fail');
            }

            $result = $this->buildCompleteErrorResponse($e);
        }

        return $result;
    }

    /**
     * @return array{
     *     device_code: string,
     *     user_code: string,
     *     verification_uri: string,
     *     verification_uri_complete: string,
     *     interval: int,
     *     expires_in: int,
     * }|array{
     *     error: string,
     *     error_description: string,
     *     message: string,
     * }
     */
    public function deviceCode(ServerRequestInterface $request): array {
        try {
            $response = $this->server->respondToDeviceAuthorizationRequest($request, new Response());
        } catch (OAuthServerException $e) {
            Yii::$app->response->statusCode = $e->getHttpStatusCode();
            return $this->buildIssueErrorResponse($e);
        }

        Yii::$app->statsd->inc('oauth.deviceCode.initialize');

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * The method is executed by the application server to which auth_token or refresh_token was given.
     *
     * Input data is a standard list of POST parameters according to the OAuth2 standard:
     * $_POST = [
     *     client_id,
     *     client_secret,
     *     redirect_uri,
     *     code,
     *     grant_type,
     * ]
     * for request with grant_type = authentication_code:
     * $_POST = [
     *     client_id,
     *     client_secret,
     *     refresh_token,
     *     grant_type,
     * ]
     *
     * @return array<mixed>
     */
    public function getToken(ServerRequestInterface $request): array {
        $params = (array)$request->getParsedBody();
        $clientId = $params['client_id'] ?? '';
        $grantType = $params['grant_type'] ?? 'null';
        try {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.attempt");

            $shouldIssueRefreshToken = false;
            $this->server->getEmitter()->subscribeOnceTo(RequestedRefreshToken::class, function() use (&$shouldIssueRefreshToken): void {
                $shouldIssueRefreshToken = true;
            });

            $response = $this->server->respondToAccessTokenRequest($request, new Response(200));
            /** @noinspection JsonEncodingApiUsageInspection at this point json error is not possible */
            $result = json_decode((string)$response->getBody(), true);
            if ($shouldIssueRefreshToken) {
                // Set the refresh_token field to keep compatibility with the old clients,
                // which will be broken in case when refresh_token field will be missing
                $result['refresh_token'] = $result['access_token'];
            }

            if (($result['expires_in'] ?? 0) <= 0) {
                if ($shouldIssueRefreshToken || $grantType === 'refresh_token') {
                    // Since some of our clients use this field to understand how long the token will live,
                    // we have to give it some value. The tokens with zero lifetime don't expire
                    // but in order not to break the clients storing the value as integer on 32-bit systems,
                    // let's calculate the value based on the unsigned maximum for this type
                    $result['expires_in'] = 2 ** 31 - time();
                } else {
                    unset($result['expires_in']);
                }
            }

            Yii::$app->statsd->inc("oauth.issueToken_client.{$clientId}");
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.success");
        } catch (OAuthServerException $e) {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.fail");
            Yii::$app->response->statusCode = $e->getHttpStatusCode();

            $result = $this->buildIssueErrorResponse($e);
        }

        return $result;
    }

    /**
     * Token revocation endpoint (RFC 7009).
     *
     * The tokens can be revoked only for the account and client pair (see OauthSession::isRevokedForTokenIssuedAt()),
     * so the revocation of a single token revokes all the tokens issued to the client for the token's account.
     * The client's approval itself is kept.
     *
     * Input data is a standard list of POST parameters:
     * $_POST = [
     *     token,
     *     token_type_hint, // isn't used, since the access_token and the refresh_token are the same value
     *     client_id,
     *     client_secret, // can be passed via the Basic auth as well
     * ]
     *
     * @return array<string, string>
     */
    public function revokeToken(ServerRequestInterface $request): array {
        Yii::$app->statsd->inc('oauth.revokeToken.attempt');

        [$clientId, $clientSecret] = $this->getClientCredentials($request);
        if ($clientId === null || !(new ClientRepository())->validateClient($clientId, $clientSecret, null)) {
            Yii::$app->statsd->inc('oauth.revokeToken.fail');
            Yii::$app->response->statusCode = 401;

            return [
                'error' => 'invalid_client',
                'error_description' => 'Client authentication failed',
            ];
        }

        $token = ((array)$request->getParsedBody())['token'] ?? null;
        if (!is_string($token) || $token === '') {
            Yii::$app->statsd->inc('oauth.revokeToken.fail');
            Yii::$app->response->statusCode = 400;

            return [
                'error' => 'invalid_request',
                'error_description' => 'The request is missing a required parameter. Check the `token` parameter.',
            ];
        }

        $this->revokeTokensByToken($token, $clientId);
        Yii::$app->statsd->inc('oauth.revokeToken.success');

        return [];
    }

    /**
     * The invalid tokens, the tokens issued to another client and the already revoked tokens
     * are silently ignored, since they don't cause an error response (RFC 7009 section 2.2).
     */
    private function revokeTokensByToken(string $jwt, string $clientId): void {
        try {
            $token = Yii::$app->tokens->parse($jwt);
        } catch (InvalidArgumentException) {
            return;
        }

        if (!$token instanceof UnencryptedToken || !Yii::$app->tokens->verify($token)) {
            return;
        }

        $reader = new TokenReader($token);
        $accountId = $reader->getAccountId();
        // The client_credentials tokens aren't related to any account, so there is nothing to revoke
        if ($accountId === null || $reader->getClientId() !== $clientId) {
            return;
        }

        /** @var \DateTimeImmutable $iat */
        $iat = $token->claims()->get('iat');
        /** @var OauthSession|null $session */
        $session = OauthSession::findOne(['account_id' => $accountId, 'client_id' => $clientId]);
        // The already revoked token mustn't revoke the tokens issued after its revocation
        if ($session === null || $session->isRevokedForTokenIssuedAt($iat)) {
            return;
        }

        // The revocation is checked with the seconds precision, so the token issued in the same second
        // as the revocation won't be considered as revoked. Ensure that at least the passed token is revoked
        $session->revoked_at = max(time(), $iat->getTimestamp() + 1);
        Assert::true($session->save());
    }

    /**
     * The client credentials can be passed either via the request body (client_secret_post)
     * or via the Basic auth (client_secret_basic, RFC 6749 section 2.3.1).
     *
     * @return array{string|null, string|null}
     */
    private function getClientCredentials(ServerRequestInterface $request): array {
        $authorization = $request->getHeaderLine('Authorization');
        if (str_starts_with(strtolower($authorization), 'basic ')) {
            $credentials = base64_decode(substr($authorization, 6), true);
            if ($credentials !== false && str_contains($credentials, ':')) {
                [$clientId, $clientSecret] = explode(':', $credentials, 2);

                return [urldecode($clientId), urldecode($clientSecret)];
            }
        }

        $body = (array)$request->getParsedBody();
        $clientId = $body['client_id'] ?? null;
        $clientSecret = $body['client_secret'] ?? null;

        return [
            is_string($clientId) && $clientId !== '' ? $clientId : null,
            is_string($clientSecret) ? $clientSecret : null,
        ];
    }

    private function findClient(string $clientId): ?OauthClient {
        return OauthClient::findOne(['id' => $clientId]);
    }

    /**
     * The method checks whether the current user can be automatically authorized for the specified client
     * without requesting access to the necessary list of scopes
     */
    private function canBeAutoApproved(Account $account, OauthClient $client, AuthorizationRequestInterface $request): bool {
        // The device code flow is initiated by anyone who knows the client_id, and the user code can be passed
        // to the user via a link. So the user must always confirm the authorization explicitly (RFC 8628 section 5.4)
        if ($request->getGrantTypeId() === 'urn:ietf:params:oauth:grant-type:device_code') {
            return false;
        }

        if ($client->is_trusted) {
            return true;
        }

        // There is no sense in the authorization without any scope, so always ask the user explicitly for it
        $requestedScopes = $this->getScopesList($request);
        if (empty($requestedScopes)) {
            return false;
        }

        // The session's revoked_at field is related only to the issued tokens and doesn't affect the approval
        $session = $this->findOauthSession($account, $client);
        if ($session === null) {
            return false;
        }

        return empty(array_diff($requestedScopes, $session->getScopes()));
    }

    private function storeOauthSession(Account $account, OauthClient $client, AuthorizationRequestInterface $request): void {
        $session = $this->findOauthSession($account, $client);
        if ($session === null) {
            $session = new OauthSession();
            $session->account_id = $account->id;
            $session->client_id = $client->id;
        }

        $session->scopes = ScopesMapper::normalizeScopes(array_merge($session->getScopes(), $this->getScopesList($request)));
        $session->last_used_at = time();

        Assert::true($session->save());
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     *
     * @return array<mixed>
     */
    private function buildSuccessResponse(ServerRequestInterface $request, OauthClient $client, array $scopes): array {
        return [
            'success' => true,
            // We return only those keys which are related to the OAuth2 standard parameters
            'oAuth' => array_intersect_key($request->getQueryParams(), array_flip([
                'client_id',
                'redirect_uri',
                'response_type',
                'scope',
                'state',
                'user_code',
            ])),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'description' => $client->description,
            ],
            'session' => [
                'scopes' => $this->buildScopesArray($scopes),
            ],
        ];
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     * @return string[]
     */
    private function buildScopesArray(array $scopes): array {
        $result = [];
        foreach ($scopes as $scope) {
            $result[] = $scope->getIdentifier();
        }

        return $result;
    }

    /**
     * @return array{
     *     success: false,
     *     error: string,
     *     parameter: string|null,
     *     statusCode: int,
     *     redirectUri?: string,
     * }
     */
    private function buildCompleteErrorResponse(OAuthServerException $e): array {
        $hint = $e->getPayload()['hint'] ?? '';
        $parameter = null;
        if (preg_match('/the `(\w+)` scope/', $hint, $matches)) {
            $parameter = $matches[1];
        }

        if ($parameter === null && $hint === 'user_code') {
            $parameter = $hint;
        }

        $response = [
            'success' => false,
            'error' => $e->getErrorType(),
            'parameter' => $parameter,
            'statusCode' => $e->getHttpStatusCode(),
        ];

        if ($e->hasRedirect()) {
            // The library builds the redirect uri with the state param already appended ("http://ely.by?state=abc")
            // or with a trailing query delimiter ("http://ely.by?"), so the payload can't be just concatenated to it
            $response['redirectUri'] = (string)Uri::withQueryValues(
                new Uri((string)$e->getRedirectUri()),
                array_merge($e->getPayload(), [
                    'iss' => $this->getIssuer(),
                ]),
            );
        }

        if ($e->getHttpStatusCode() !== 200) {
            Yii::$app->response->setStatusCode($e->getHttpStatusCode());
        }

        return $response;
    }

    /**
     * Raw error messages aren't very informative for the end user, as they don't contain
     * information about the parameter that caused the error.
     * This method is intended to build a more understandable description.
     *
     * Part of the existing texts are the legacy from the previous implementation.
     *
     * The standard field for the description is "error_description" (RFC 6749 section 5.2),
     * but the "message" field is kept for the backward compatibility.
     *
     * @return array{
     *     error: string,
     *     error_description: string,
     *     message: string,
     * }
     */
    private function buildIssueErrorResponse(OAuthServerException $e): array {
        $errorType = $e->getErrorType();
        $message = $e->getMessage();
        $hint = $e->getHint();
        switch ($hint) {
            case 'Cannot decrypt the authorization code':
                // RFC 6749 doesn't allow double quotes in the error_description
                $message .= ' Check the `code` parameter.';
                break;
        }

        return [
            'error' => $errorType,
            'error_description' => $message,
            'message' => $message,
        ];
    }

    private function createAcceptRequiredException(): OAuthServerException {
        return new OAuthServerException('Client must accept authentication request.', 0, 'accept_required', 401);
    }

    /**
     * @return list<non-empty-string>
     */
    private function getScopesList(AuthorizationRequestInterface $request): array {
        return array_values(array_map(fn(ScopeEntityInterface $scope) => $scope->getIdentifier(), $request->getScopes()));
    }

    /**
     * @return non-empty-string
     */
    private function getIssuer(): string {
        $issuer = Yii::$app->request->getHostInfo();
        Assert::stringNotEmpty($issuer);

        return $issuer;
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private function findOauthSession(Account $account, OauthClient $client): ?OauthSession {
        return $account->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
    }

}
