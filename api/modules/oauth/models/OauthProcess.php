<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\components\OAuth2\Entities\UserEntity;
use api\rbac\Permissions as P;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;
use Yii;

class OauthProcess {

    private const INTERNAL_PERMISSIONS_TO_PUBLIC_SCOPES = [
        P::OBTAIN_OWN_ACCOUNT_INFO => 'account_info',
        P::OBTAIN_ACCOUNT_EMAIL => 'account_email',
    ];

    /**
     * @var AuthorizationServer
     */
    private $server;

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
     * @return array
     */
    public function validate(): array {
        try {
            $request = $this->getRequest();
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
     * @return array
     */
    public function complete(): array {
        try {
            Yii::$app->statsd->inc('oauth.complete.attempt');

            $request = $this->getRequest();
            $authRequest = $this->server->validateAuthorizationRequest($request);
            /** @var Account $account */
            $account = Yii::$app->user->identity->getAccount();
            /** @var OauthClient $client */
            $client = $this->findClient($authRequest->getClient()->getIdentifier());

            $approved = $this->canAutoApprove($account, $client, $authRequest);
            if (!$approved) {
                Yii::$app->statsd->inc('oauth.complete.approve_required');

                $acceptParam = ((array)$request->getParsedBody())['accept'] ?? null;
                if ($acceptParam === null) {
                    throw $this->createAcceptRequiredException();
                }

                $approved = in_array($acceptParam, [1, '1', true, 'true'], true);
                if ($approved) {
                    $this->storeOauthSession($account, $client, $authRequest);
                }
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
     * @return array
     */
    public function getToken(): array {
        $request = $this->getRequest();
        $params = (array)$request->getParsedBody();
        $grantType = $params['grant_type'] ?? 'null';
        try {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.attempt");

            $responseObj = new Response(200);
            $this->server->respondToAccessTokenRequest($request, $responseObj);
            $clientId = $params['client_id'];

            // TODO: build response from the responseObj
            $response = [];

            Yii::$app->statsd->inc("oauth.issueToken_client.{$clientId}");
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.success");
        } catch (OAuthServerException $e) {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.fail");
            Yii::$app->response->statusCode = $e->getHttpStatusCode();

            $response = $this->buildIssueErrorResponse($e);
        }

        return $response;
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
    private function canAutoApprove(Account $account, OauthClient $client, AuthorizationRequest $request): bool {
        if ($client->is_trusted) {
            return true;
        }

        $session = $this->findOauthSession($account, $client);
        if ($session === null) {
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
            $response['redirectUri'] = $e->getRedirectUri();
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
     * Part of the existing texts is a legacy from the previous implementation.
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
        }

        return [
            'error' => $errorType,
            'message' => $message,
        ];
    }

    private function getRequest(): ServerRequestInterface {
        return ServerRequest::fromGlobals();
    }

    private function createAcceptRequiredException(): OAuthServerException {
        return new OAuthServerException(
            'Client must accept authentication request.',
            0,
            'accept_required',
            401
        );
    }

    private function getScopesList(AuthorizationRequest $request): array {
        // TODO: replace with an arrow function in PHP 7.4
        return array_map(function(ScopeEntityInterface $scope): string {
            return $scope->getIdentifier();
        }, $request->getScopes());
    }

    private function findOauthSession(Account $account, OauthClient $client): ?OauthSession {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $account->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
    }

}
