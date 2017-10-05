<?php
namespace api\controllers;

use api\models\OauthAccountInfo;
use common\rbac\Permissions as P;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class IdentityInfoController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => [P::OBTAIN_ACCOUNT_INFO],
                        'roleParams' => function() {
                            /** @noinspection NullPointerExceptionInspection */
                            return [
                                'accountId' => Yii::$app->user->getIdentity()->getAccount()->id,
                            ];
                        },
                    ],
                ],
            ],
        ]);
    }

    public function actionIndex(): array {
        /** @noinspection NullPointerExceptionInspection */
        return (new OauthAccountInfo(Yii::$app->user->getIdentity()->getAccount()))->info();
    }

}
