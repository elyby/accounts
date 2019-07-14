<?php
namespace api\modules\oauth\models;

use api\components\OAuth2\Exception\AcceptRequiredException;
use api\components\OAuth2\Exception\AccessDeniedException;
use api\components\OAuth2\Grants\AuthCodeGrant;
use api\components\OAuth2\Grants\AuthorizeParams;
use common\models\Account;
use common\models\OauthClient;
use common\rbac\Permissions as P;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\InvalidGrantException;
use League\OAuth2\Server\Exception\OAuthException;
use League\OAuth2\Server\Grant\GrantTypeInterface;
use Yii;
use yii\helpers\ArrayHelper;

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
            $authParams = $this->getAuthorizationCodeGrant()->checkAuthorizeParams();
            $client = $authParams->getClient();
            /** @var OauthClient $clientModel */
            $clientModel = $this->findClient($client->getId());
            $response = $this->buildSuccessResponse(
                Yii::$app->request->getQueryParams(),
                $clientModel,
                $authParams->getScopes()
            );
        } catch (OAuthException $e) {
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
            $grant = $this->getAuthorizationCodeGrant();
            $authParams = $grant->checkAuthorizeParams();
            /** @var Account $account */
            $account = Yii::$app->user->identity->getAccount();
            /** @var \common\models\OauthClient $clientModel */
            $clientModel = $this->findClient($authParams->getClient()->getId());

            if (!$this->canAutoApprove($account, $clientModel, $authParams)) {
                Yii::$app->statsd->inc('oauth.complete.approve_required');
                $isAccept = Yii::$app->request->post('accept');
                if ($isAccept === null) {
                    throw new AcceptRequiredException();
                }

                if (!$isAccept) {
                    throw new AccessDeniedException($authParams->getRedirectUri());
                }
            }

            $redirectUri = $grant->newAuthorizeRequest('user', $account->id, $authParams);
            $response = [
                'success' => true,
                'redirectUri' => $redirectUri,
            ];
            Yii::$app->statsd->inc('oauth.complete.success');
        } catch (OAuthException $e) {
            if (!$e instanceof AcceptRequiredException) {
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
        $grantType = Yii::$app->request->post('grant_type', 'null');
        try {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.attempt");
            $response = $this->server->issueAccessToken();
            $clientId = Yii::$app->request->post('client_id');
            Yii::$app->statsd->inc("oauth.issueToken_client.{$clientId}");
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.success");
        } catch (OAuthException $e) {
            Yii::$app->statsd->inc("oauth.issueToken_{$grantType}.fail");
            Yii::$app->response->statusCode = $e->httpStatusCode;
            $response = [
                'error' => $e->errorType,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    private function findClient(string $clientId): ?OauthClient {
        return OauthClient::findOne($clientId);
    }

    /**
     * The method checks whether the current user can be automatically authorized for the specified client
     * without requesting access to the necessary list of scopes
     *
     * @param Account $account
     * @param OauthClient $client
     * @param AuthorizeParams $oauthParams
     *
     * @return bool
     */
    private function canAutoApprove(Account $account, OauthClient $client, AuthorizeParams $oauthParams): bool {
        if ($client->is_trusted) {
            return true;
        }

        /** @var \common\models\OauthSession|null $session */
        $session = $account->getOauthSessions()->andWhere(['client_id' => $client->id])->one();
        if ($session !== null) {
            $existScopes = $session->getScopes()->members();
            if (empty(array_diff(array_keys($oauthParams->getScopes()), $existScopes))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $queryParams
     * @param OauthClient $client
     * @param \api\components\OAuth2\Entities\ScopeEntity[] $scopes
     *
     * @return array
     */
    private function buildSuccessResponse(array $queryParams, OauthClient $client, array $scopes): array {
        return [
            'success' => true,
            // We return only those keys which are related to the OAuth2 standard parameters
            'oAuth' => array_intersect_key($queryParams, array_flip([
                'client_id',
                'redirect_uri',
                'response_type',
                'scope',
                'state',
            ])),
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'description' => ArrayHelper::getValue($queryParams, 'description', $client->description),
            ],
            'session' => [
                'scopes' => $this->fixScopesNames(array_keys($scopes)),
            ],
        ];
    }

    private function fixScopesNames(array $scopes): array {
        foreach ($scopes as &$scope) {
            if (isset(self::INTERNAL_PERMISSIONS_TO_PUBLIC_SCOPES[$scope])) {
                $scope = self::INTERNAL_PERMISSIONS_TO_PUBLIC_SCOPES[$scope];
            }
        }

        return $scopes;
    }

    private function buildErrorResponse(OAuthException $e): array {
        $response = [
            'success' => false,
            'error' => $e->errorType,
            'parameter' => $e->parameter,
            'statusCode' => $e->httpStatusCode,
        ];

        if ($e->shouldRedirect()) {
            $response['redirectUri'] = $e->getRedirectUri();
        }

        if ($e->httpStatusCode !== 200) {
            Yii::$app->response->setStatusCode($e->httpStatusCode);
        }

        return $response;
    }

    private function getGrant(string $grantType = null): GrantTypeInterface {
        return $this->server->getGrantType($grantType ?? Yii::$app->request->get('grant_type'));
    }

    private function getAuthorizationCodeGrant(): AuthCodeGrant {
        /** @var GrantTypeInterface $grantType */
        $grantType = $this->getGrant('authorization_code');
        if (!$grantType instanceof AuthCodeGrant) {
            throw new InvalidGrantException('authorization_code grant have invalid realisation');
        }

        return $grantType;
    }

}
