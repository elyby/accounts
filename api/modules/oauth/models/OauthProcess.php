<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\rbac\Permissions as P;
use common\models\Account;
use common\models\OauthClient;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ServerRequestInterface;
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
            $response = $this->buildErrorResponse($e);
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
            /** @var OauthClient $clientModel */
            $clientModel = $this->findClient($authRequest->getClient()->getIdentifier());

            if (!$this->canAutoApprove($account, $clientModel, $authRequest)) {
                Yii::$app->statsd->inc('oauth.complete.approve_required');

                $accept = ((array)$request->getParsedBody())['accept'] ?? null;
                if ($accept === null) {
                    throw $this->createAcceptRequiredException();
                }

                if (!in_array($accept, [1, '1', true, 'true'], true)) {
                    throw OAuthServerException::accessDenied(null, $authRequest->getRedirectUri());
                }
            }

            $responseObj = $this->server->completeAuthorizationRequest($authRequest, new Response(200));

            $response = [
                'success' => true,
                'redirectUri' => $responseObj->getHeader('Location'), // TODO: ensure that this is correct type and behavior
            ];

            Yii::$app->statsd->inc('oauth.complete.success');
        } catch (OAuthServerException $e) {
            if ($e->getErrorType() === 'accept_required') {
                Yii::$app->statsd->inc('oauth.complete.fail');
            }

            $response = $this->buildErrorResponse($e);
        }

        return $response;
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

            $response = [
                'error' => $e->getErrorType(),
                'message' => $e->getMessage(), // TODO: use hint field?
            ];
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

        /** @var \common\models\OauthSession|null $session */
        $session = $account->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
        if ($session !== null) {
            $existScopes = $session->getScopes()->members();
            if (empty(array_diff(array_keys($request->getScopes()), $existScopes))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ServerRequestInterface $request
     * @param OauthClient $client
     * @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes
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
     * @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes
     * @return array
     */
    private function buildScopesArray(array $scopes): array {
        $result = [];
        foreach ($scopes as $scope) {
            $result[] = self::INTERNAL_PERMISSIONS_TO_PUBLIC_SCOPES[$scope->getIdentifier()] ?? $scope->getIdentifier();
        }

        return $result;
    }

    private function buildErrorResponse(OAuthServerException $e): array {
        $response = [
            'success' => false,
            'error' => $e->getErrorType(),
            // 'parameter' => $e->parameter, // TODO: if this is necessary, the parameter can be extracted from the hint
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

}
