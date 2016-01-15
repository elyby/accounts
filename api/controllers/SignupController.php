<?php
namespace api\controllers;

use api\models\RegistrationForm;
use Yii;
use yii\filters\AccessControl;

class SignupController extends Controller {

    public function behaviors() {
        return array_merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['register'],
                'rules' => [
                    [
                        'actions' => ['register'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
        ]);
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

}
