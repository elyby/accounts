<?php
namespace api\controllers;

use common\models\Account;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class UsersController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['current'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'current' => ['GET'],
        ];
    }

    public function actionCurrent() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;

        return [
            'id' => $account->id,
            'username' => $account->username,
            'email' => $account->email,
            'shouldChangePassword' => $account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY,
        ];
    }

}
