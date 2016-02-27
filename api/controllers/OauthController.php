<?php
namespace api\controllers;

use common\components\oauth\Exception\AcceptRequiredException;
use common\components\oauth\Exception\AccessDeniedException;
use common\models\OauthClient;
use common\models\OauthScope;
use League\OAuth2\Server\Exception\OAuthException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class OauthController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['validate', 'issue-token'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['validate', 'issue-token'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['complete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'validate'    => ['GET'],
            'complete'    => ['POST'],
            'issue-token' => ['POST'],
        ];
    }

    /**
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    protected function getServer() {
        /** @var \common\components\oauth\Component $oauth */
        $oauth = Yii::$app->get('oauth');
        return $oauth->authServer;
    }

    /**
     * @return \League\OAuth2\Server\Grant\AuthCodeGrant
     */
    protected function getGrantType() {
        return $this->getServer()->getGrantType('authorization_code');
    }

    /**
     * Запрос, который должен проверить переданные параметры oAuth авторизации
     * и сформировать ответ для нашего приложения на фронте
     *
     * Входными данными является стандартный список GET параметров по стандарту oAuth:
     * $_GET = [
     *     client_id,
     *     redirect_uri,
     *     response_type,
     *     scope,
     *     state,
     * ]
     *
     * Кроме того можно передать значения description для переопределения описания приложения.
     *
     * @return array|\yii\web\Response
     */
    public function actionValidate() {
        try {
            $authParams = $this->getGrantType()->checkAuthorizeParams();
            /** @var \League\OAuth2\Server\Entity\ClientEntity $client */
            $client = $authParams['client'];
            /** @var \common\models\OauthClient $clientModel */
            $clientModel = OauthClient::findOne($client->getId());
            $response = $this->buildSuccessResponse(
                Yii::$app->request->getQueryParams(),
                $clientModel,
                $authParams['scopes']
            );
        } catch (OAuthException $e) {
            $response = $this->buildErrorResponse($e);
        }

        return $response;
    }

    /**
     * Метод выполняется генерацию авторизационного кода (auth_code) и формирование ссылки
     * для дальнейшнешл редиректа пользователя назад на сайт клиента
     *
     * Входными данными является всё те же параметры, что были необходимы для валидации:
     * $_GET = [
     *     client_id,
     *     redirect_uri,
     *     response_type,
     *     scope,
     *     state,
     * ];
     * А также поле accept, которое показывает, что пользователь нажал на кнопку "Принять". Если поле присутствует,
     * то оно будет интерпретироваться как любое приводимое к false значение. В ином случае, значение будет
     * интерпретировано, как положительный исход.
     *
     * @return array|\yii\web\Response
     */
    public function actionComplete() {
        $grant = $this->getGrantType();
        try {
            $authParams = $grant->checkAuthorizeParams();
            $account = $this->getAccount();
            /** @var \League\OAuth2\Server\Entity\ClientEntity $client */
            $client = $authParams['client'];
            /** @var \common\models\OauthClient $clientModel */
            $clientModel = OauthClient::findOne($client->getId());

            if (!$account->canAutoApprove($clientModel, $authParams['scopes'])) {
                $isAccept = Yii::$app->request->post('accept');
                $isAccept = null;
                if ($isAccept === null) {
                    throw new AcceptRequiredException();
                }

                if (!$isAccept) {
                    throw new AccessDeniedException($authParams['redirect_uri']);
                }
            }

            $redirectUri = $grant->newAuthorizeRequest('user', $account->id, $authParams);
            $response = [
                'success' => true,
                'redirectUri' => $redirectUri,
            ];
        } catch (OAuthException $e) {
            $response = $this->buildErrorResponse($e);
        }

        return $response;
    }

    /**
     * Метод выполняется сервером приложения, которому был выдан auth_token или refresh_token.
     *
     * Входными данными является стандартный список GET параметров по стандарту oAuth:
     * $_GET = [
     *     client_id,
     *     client_secret,
     *     redirect_uri,
     *     code,
     *     grant_type,
     * ]
     * для запроса grant_type = authentication_code.
     * $_GET = [
     *     client_id,
     *     client_secret,
     *     refresh_token,
     *     grant_type,
     * ]
     *
     * @return array
     */
    public function actionIssueToken() {
        $this->attachRefreshTokenGrantIfNeedle();
        try {
            $response = $this->getServer()->issueAccessToken();
        } catch (OAuthException $e) {
            Yii::$app->response->statusCode = $e->httpStatusCode;
            $response = [
                'error' => $e->errorType,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    private function attachRefreshTokenGrantIfNeedle() {
        $grantType = Yii::$app->request->post('grant_type');
        if ($grantType === 'authorization_code' && Yii::$app->request->post('code')) {
            $authCode = Yii::$app->request->post('code');
            $codeModel = $this->getServer()->getAuthCodeStorage()->get($authCode);
            if ($codeModel === null) {
                return;
            }

            $scopes = $codeModel->getScopes();
            if (array_search(OauthScope::OFFLINE_ACCESS, array_keys($scopes)) === false) {
                return;
            }
        } elseif ($grantType === 'refresh_token') {
            // Это валидный кейс
        } else {
            return;
        }

        $this->getServer()->addGrantType(new RefreshTokenGrant());
    }

    /**
     * @param array $queryParams
     * @param OauthClient $clientModel
     * @param \League\OAuth2\Server\Entity\ScopeEntity[] $scopes
     *
     * @return array
     */
    private function buildSuccessResponse($queryParams, OauthClient $clientModel, $scopes) {
        return [
            'success' => true,
            // Возвращаем только те ключи, которые имеют реальное отношение к oAuth параметрам
            'oAuth' => array_intersect_key($queryParams, array_flip([
                'client_id',
                'redirect_uri',
                'response_type',
                'scope',
                'state',
            ])),
            'client' => [
                'id' => $clientModel->id,
                'name' => $clientModel->name,
                'description' => ArrayHelper::getValue($queryParams, 'description', $clientModel->description),
            ],
            'session' => [
                'scopes' => array_keys($scopes),
            ],
        ];
    }

    private function buildErrorResponse(OAuthException $e) {
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

}
