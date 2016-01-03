<?php
namespace api\modules\login\controllers;

use api\controllers\Controller;
use api\modules\login\models\AuthenticationForm;
use Yii;
use yii\filters\AccessControl;

class AuthenticationController extends Controller {

    public function behaviors() {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['login-info'],
                'rules' => [
                    [
                        'actions' => ['login-info'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'loginInfo' => ['post'],
        ];
    }

    public function actionLoginInfo() {
        $model = new AuthenticationForm();
        $model->load(Yii::$app->request->post());
        if (!$model->login()) {
            return [
                'success' => false,
                'errors' => $model->getErrors(),
            ];
        }

        return [
            'success' => true,
        ];
    }

}
