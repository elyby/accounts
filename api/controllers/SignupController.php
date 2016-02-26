<?php
namespace api\controllers;

use api\models\ConfirmEmailForm;
use api\models\RegistrationForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class SignupController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['index', 'confirm'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'confirm'],
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

    public function actionIndex() {
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
        if (!($jwt = $model->confirm())) {
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
