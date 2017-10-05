<?php
namespace api\controllers;

use api\models\OauthProcess;
use common\rbac\Permissions as P;
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
                        'allow' => true,
                        'actions' => ['complete'],
                        'roles' => [P::COMPLETE_OAUTH_FLOW],
                        'roleParams' => function() {
                            return [
                                'accountId' => Yii::$app->user->identity->getAccount()->id,
                            ];
                        },
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
