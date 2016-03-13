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

}
