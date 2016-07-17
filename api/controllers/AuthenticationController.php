<?php
namespace api\controllers;

use api\models\authentication\ForgotPasswordForm;
use api\models\authentication\LoginForm;
use api\models\authentication\LogoutForm;
use api\models\authentication\RecoverPasswordForm;
use api\models\authentication\RefreshTokenForm;
use common\helpers\Error as E;
use common\helpers\StringHelper;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class AuthenticationController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['login', 'forgot-password', 'recover-password', 'refresh-token'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'forgot-password', 'recover-password', 'refresh-token'],
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

    public function verbs() {
        return [
            'login' => ['POST'],
            'logout' => ['POST'],
            'forgot-password' => ['POST'],
            'recover-password' => ['POST'],
            'refresh-token' => ['POST'],
        ];
    }

    public function actionLogin() {
        $model = new LoginForm();
        $model->load(Yii::$app->request->post());
        if (($result = $model->login()) === false) {
            $data = [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];

            if (ArrayHelper::getValue($data['errors'], 'login') === E::ACCOUNT_NOT_ACTIVATED) {
                $data['data']['email'] = $model->getAccount()->email;
            }

            return $data;
        }

        return array_merge([
            'success' => true,
        ], $result->getAsResponse());
    }

    public function actionLogout() {
        $form = new LogoutForm();
        $form->logout();

        return [
            'success' => true,
        ];
    }

    public function actionForgotPassword() {
        $model = new ForgotPasswordForm();
        $model->load(Yii::$app->request->post());
        if ($model->forgotPassword() === false) {
            $data = [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];

            if (ArrayHelper::getValue($data['errors'], 'login') === E::RECENTLY_SENT_MESSAGE) {
                $emailActivation = $model->getEmailActivation();
                $data['data'] = [
                    'canRepeatIn' => $emailActivation->canRepeatIn(),
                    'repeatFrequency' => $emailActivation->repeatTimeout,
                ];
            }

            return $data;
        }

        $emailActivation = $model->getEmailActivation();
        $response = [
            'success' => true,
            'data' => [
                'canRepeatIn' => $emailActivation->canRepeatIn(),
                'repeatFrequency' => $emailActivation->repeatTimeout,
            ],
        ];

        if ($model->getLoginAttribute() !== 'email') {
            $response['data']['emailMask'] = StringHelper::getEmailMask($model->getAccount()->email);
        }

        return $response;
    }

    public function actionRecoverPassword() {
        $model = new RecoverPasswordForm();
        $model->load(Yii::$app->request->post());
        if (($result = $model->recoverPassword()) === false) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return array_merge([
            'success' => true,
        ], $result->getAsResponse());
    }

    public function actionRefreshToken() {
        $model = new RefreshTokenForm();
        $model->load(Yii::$app->request->post());
        if (($result = $model->renew()) === false) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return array_merge([
            'success' => true,
        ], $result->getAsResponse());
    }

}
