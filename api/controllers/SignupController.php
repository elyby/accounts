<?php
namespace api\controllers;

use api\models\ConfirmEmailForm;
use api\models\RegistrationForm;
use Yii;
use yii\filters\AccessControl;

class SignupController extends Controller {

    public function behaviors() {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['register', 'confirm'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'register' => ['POST'],
            'confirm' => ['POST'],
        ];
    }

    public function actionRegister() {
        $model = new RegistrationForm();
        $model->load(Yii::$app->request->post());
        if (!$model->signup()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionConfirm() {
        $model = new ConfirmEmailForm();
        $model->load(Yii::$app->request->post());
        if (!$model->confirm()) {
            return [
                'success' => false,
                'errors' => $this->normalizeModelErrors($model->getErrors()),
            ];
        }

        // TODO: не уверен, что логин должен быть здесь + нужно разобраться с параметрами установки куки авторизации и сессии
        $activationCode = $model->getActivationCodeModel();
        $account = $activationCode->account;
        Yii::$app->user->login($account);

        return [
            'success' => true,
        ];
    }

}
