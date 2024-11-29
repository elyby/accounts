<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\components\OAuth2\Entities\UserEntity;
use api\components\OAuth2\Events\RequestedRefreshToken;
use api\rbac\Permissions as P;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;
use Yii;

class OauthProcess {

    private const array INTERNAL_PERMISSIONS_TO_PUBLIC_SCOPES = [
        P::OBTAIN_OWN_ACCOUNT_INFO => 'account_info',
        P::OBTAIN_ACCOUNT_EMAIL => 'account_email',
    ];

    private AuthorizationServer $server;

    public function __construct(AuthorizationServer $server) {
        $this->server = $server;
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
     * In addition, you can pass the description value to override the application's description.
     *
     * @param ServerRequestInterface $request
     * @return array
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
     * @param ServerRequestInterface $request
     * @return array
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
                throw $this->createAcceptRequiredException();
            }

            Yii::$app->statsd->inc('oauth.complete.approve_required');

            if ($acceptParam === null && $canBeAutoApproved) {
                $approved = true;
            } else {
                $approved = in_array($acceptParam, [1, '1', true, 'true'], true);
            }

            if ($approved) {
                $this->storeOauthSession($account, $client, $authRequest);
            }

            $authRequest->setUser(new UserEntity($account->id));
            $authRequest->setAuthorizationApproved($approved);
            $response = $this->server->completeAuthorizationRequest($authRequest, new Response(200));

            $result = [
                'success' => true,
                'redirectUri' => $response->getHeaderLine('Location'),
            ];

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
     * @param ServerRequestInterface $request
     * @return array
     */
    public function getToken(ServerRequestInterface $request): array {
        $params = (array)$request->getParsedBody();
        $clientId = $params['client_id'] ?? '';
        $grantType = $params['grant_type'] ?? 'null';
        try {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.attempt");

            $shouldIssueRefreshToken = false;
            $this->server->getEmitter()->subscribeOnceTo(RequestedRefreshToken::class, function() use (&$shouldIssueRefreshToken) {
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

    private function findClient(string $clientId): ?OauthClient {
        return OauthClient::findOne(['id' => $clientId]);
    }

    /**
     * The method checks whether the current user can be automatically authorized for the specified client
     * without requesting access to the necessary list of scopes
     *
     * @param Account $account
     * @param OauthClient $client
     * @param AuthorizationRequest $request
     *
     * @return bool
     */
    private function canBeAutoApproved(Account $account, OauthClient $client, AuthorizationRequest $request): bool {
        if ($client->is_trusted) {
            return true;
        }

        $session = $this->findOauthSession($account, $client);
        if ($session === null) {
            return false;
        }

        if ($session->isRevoked()) {
            return false;
        }

        return empty(array_diff($this->getScopesList($request), $session->getScopes()));
    }

    private function storeOauthSession(Account $account, OauthClient $client, AuthorizationRequest $request): void {
        $session = $this->findOauthSession($account, $client);
        if ($session === null) {
            $session = new OauthSession();
            $session->account_id = $account->id;
            $session->client_id = $client->id;
        }

        $session->scopes = array_unique(array_merge($session->getScopes(), $this->getScopesList($request)));
        $session->last_used_at = time();

        Assert::true($session->save());
    }

    /**
     * @param ServerRequestInterface $request
     * @param OauthClient $client
     * @param ScopeEntityInterface[] $scopes
     *
     * @return array
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
            ])),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'description' => $request->getQueryParams()['description'] ?? $client->description,
            ],
            'session' => [
                'scopes' => $this->buildScopesArray($scopes),
            ],
        ];
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     * @return array
     */
    private function buildScopesArray(array $scopes): array {
        $result = [];
        foreach ($scopes as $scope) {
            $result[] = self::INTERNAL_PERMISSIONS_TO_PUBLIC_SCOPES[$scope->getIdentifier()] ?? $scope->getIdentifier();
        }

        return $result;
    }

    private function buildCompleteErrorResponse(OAuthServerException $e): array {
        $hint = $e->getPayload()['hint'] ?? '';
        if (preg_match('/the `(\w+)` scope/', $hint, $matches)) {
            $parameter = $matches[1];
        }

        $response = [
            'success' => false,
            'error' => $e->getErrorType(),
            'parameter' => $parameter ?? null,
            'statusCode' => $e->getHttpStatusCode(),
        ];

        if ($e->hasRedirect()) {
            $response['redirectUri'] = $e->getRedirectUri() . http_build_query($e->getPayload());
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
     * @param OAuthServerException $e
     * @return array
     */
    private function buildIssueErrorResponse(OAuthServerException $e): array {
        $errorType = $e->getErrorType();
        $message = $e->getMessage();
        $hint = $e->getHint();
        switch ($hint) {
            case 'Invalid redirect URI':
                $errorType = 'invalid_client';
                $message = 'Client authentication failed.';
                break;
            case 'Cannot decrypt the authorization code':
                $message .= ' Check the "code" parameter.';
                break;
        }

        return [
            'error' => $errorType,
            'message' => $message,
        ];
    }

    private function createAcceptRequiredException(): OAuthServerException {
        return new OAuthServerException('Client must accept authentication request.', 0, 'accept_required', 401);
    }

    private function getScopesList(AuthorizationRequest $request): array {
        return array_values(array_map(function(ScopeEntityInterface $scope): string {
            return $scope->getIdentifier();
        }, $request->getScopes()));
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    private function findOauthSession(Account $account, OauthClient $client): ?OauthSession {
        return $account->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
    }

}
