<?php
namespace api\controllers;

use api\models\ForgotPasswordForm;
use api\models\LoginForm;
use api\models\RecoverPasswordForm;
use common\helpers\StringHelper;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class AuthenticationController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['login', 'forgot-password', 'recover-password'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login', 'forgot-password', 'recover-password'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'login' => ['POST'],
            'forgot-password' => ['POST'],
            'recover-password' => ['POST'],
        ];
    }

    public function actionLogin() {
        $model = new LoginForm();
        $model->load(Yii::$app->request->post());
        if (($jwt = $model->login()) === false) {
            $data = [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];

            if (ArrayHelper::getValue($data['errors'], 'login') === 'error.account_not_activated') {
                $data['data']['email'] = $model->getAccount()->email;
            }

            return $data;
        }

        return [
            'success' => true,
            'jwt' => $jwt,
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

            if (ArrayHelper::getValue($data['errors'], 'login') === 'error.email_frequency') {
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
        if (($jwt = $model->recoverPassword()) === false) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
            'jwt' => $jwt,
        ];
    }

}
