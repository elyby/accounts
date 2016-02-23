<?php
namespace api\controllers;

use api\models\LoginForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class AuthenticationController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['login'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['login'],
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
        ];
    }

    public function actionLogin() {
        $model = new LoginForm();
        $model->load(Yii::$app->request->post());
        if (($jwt = $model->login()) === false) {
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
