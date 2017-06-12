<?php
namespace api\controllers;

use api\filters\ActiveUserRule;
use api\models\OauthProcess;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class OauthController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'only' => ['complete'],
            ],
            'access' => [
                'class' => AccessControl::class,
                'only' => ['complete'],
                'rules' => [
                    [
                        'class' => ActiveUserRule::class,
                        'actions' => ['complete'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs(): array {
        return [
            'validate' => ['GET'],
            'complete' => ['POST'],
            'token'    => ['POST'],
        ];
    }

    public function actionValidate(): array {
        return $this->createOauthProcess()->validate();
    }

    public function actionComplete(): array {
        return $this->createOauthProcess()->complete();
    }

    public function actionToken(): array {
        return $this->createOauthProcess()->getToken();
    }

    private function createOauthProcess(): OauthProcess {
        return new OauthProcess(Yii::$app->oauth->authServer);
    }

}
