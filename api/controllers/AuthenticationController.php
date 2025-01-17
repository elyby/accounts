<?php
declare(strict_types=1);

namespace api\controllers;

use api\models\authentication\ForgotPasswordForm;
use api\models\authentication\LoginForm;
use api\models\authentication\RecoverPasswordForm;
use api\models\authentication\RefreshTokenForm;
use common\components\Authentication\Entities\Credentials;
use common\components\Authentication\Exceptions;
use common\components\Authentication\Exceptions\AuthenticationException;
use common\components\Authentication\LoginServiceInterface;
use common\helpers\Error as E;
use common\helpers\StringHelper;
use DateTimeImmutable;
use Yii;
use yii\base\Module;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\Request;

final class AuthenticationController extends Controller {

    public function __construct(
        string $id,
        Module $module,
        private readonly LoginServiceInterface $loginService,
        array $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'only' => ['logout'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'except' => ['refresh-token'],
                'rules' => [
                    [
                        'actions' => ['login', 'forgot-password', 'recover-password'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs(): array {
        return [
            'login' => ['POST'],
            'logout' => ['POST'],
            'forgot-password' => ['POST'],
            'recover-password' => ['POST'],
            'refresh-token' => ['POST'],
        ];
    }

    public function actionLogin(Request $request): array {
        $form = new LoginForm();
        $form->load($request->post());
        if (!$form->validate()) {
            return [
                'success' => false,
                'errors' => $form->getFirstErrors(),
            ];
        }

        try {
            $loginResult = $this->loginService->loginByCredentials(new Credentials(
                login: (string)$form->login,
                password: (string)$form->password,
                totp: (string)$form->totp,
                rememberMe: (bool)$form->rememberMe,
            ));
        } catch (AuthenticationException $e) {
            $data = [
                'success' => false,
                'errors' => match ($e::class) {
                    Exceptions\UnknownLoginException::class => ['login' => E::LOGIN_NOT_EXIST],
                    Exceptions\InvalidPasswordException::class => ['password' => E::PASSWORD_INCORRECT],
                    Exceptions\TotpRequiredException::class => ['totp' => E::TOTP_REQUIRED],
                    Exceptions\InvalidTotpException::class => ['totp' => E::TOTP_INCORRECT],
                    Exceptions\AccountBannedException::class => ['login' => E::ACCOUNT_BANNED],
                    Exceptions\AccountNotActivatedException::class => ['login' => E::ACCOUNT_NOT_ACTIVATED],
                    default => $e->getMessage(),
                },
            ];

            if ($e instanceof Exceptions\AccountNotActivatedException) {
                $data['data']['email'] = $e->account->email;
            }

            return $data;
        }

        $token = Yii::$app->tokensFactory->createForWebAccount($loginResult->account, $loginResult->session);
        $data = [
            'success' => true,
            'access_token' => $token->toString(),
            'expires_in' => $token->claims()->get('exp')->getTimestamp() - (new DateTimeImmutable())->getTimestamp(),
        ];

        if ($loginResult->session) {
            $data['refresh_token'] = $loginResult->session->refresh_token;
        }

        return $data;
    }

    public function actionLogout(): array {
        $session = Yii::$app->user->getActiveSession();
        if ($session) {
            $this->loginService->logout($session);
        }

        return [
            'success' => true,
        ];
    }

    public function actionForgotPassword(): array {
        $model = new ForgotPasswordForm();
        $model->load(Yii::$app->request->post());
        if ($model->forgotPassword() === false) {
            $data = [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];

            if (ArrayHelper::getValue($data['errors'], 'login') === E::RECENTLY_SENT_MESSAGE) {
                /** @var \common\models\confirmations\ForgotPassword $emailActivation */
                $emailActivation = $model->getEmailActivation();
                $data['data'] = [
                    'canRepeatIn' => $emailActivation->canResendAt()->getTimestamp(),
                ];
            }

            return $data;
        }

        $emailActivation = $model->getEmailActivation();
        $response = [
            'success' => true,
            'data' => [
                'canRepeatIn' => $emailActivation->canResendAt()->getTimestamp(),
            ],
        ];

        if (!str_contains((string)$model->login, '@')) {
            $response['data']['emailMask'] = StringHelper::getEmailMask($model->getAccount()->email);
        }

        return $response;
    }

    public function actionRecoverPassword(): array {
        $model = new RecoverPasswordForm();
        $model->load(Yii::$app->request->post());
        if (($result = $model->recoverPassword()) === null) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return array_merge([
            'success' => true,
        ], $result->formatAsOAuth2Response());
    }

    public function actionRefreshToken(): array {
        $model = new RefreshTokenForm();
        $model->load(Yii::$app->request->post());
        if (($result = $model->renew()) === null) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        $response = $result->formatAsOAuth2Response();
        unset($response['refresh_token']);

        return array_merge([
            'success' => true,
        ], $response);
    }

}
