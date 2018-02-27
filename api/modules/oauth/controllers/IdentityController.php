<?php
namespace api\modules\oauth\controllers;

use api\controllers\Controller;
use api\modules\oauth\models\IdentityInfo;
use common\rbac\Permissions as P;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class IdentityController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(Controller::behaviors(), [
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
        return (new IdentityInfo(Yii::$app->user->getIdentity()->getAccount()))->info();
    }

}
