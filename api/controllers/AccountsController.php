<?php
namespace api\controllers;

use api\models\ChangePasswordForm;
use common\models\Account;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class AccountsController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['current', 'change-password'],
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
            'change-password' => ['POST'],
        ];
    }

    public function actionCurrent() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;

        return [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'email' => $account->email,
            'shouldChangePassword' => $account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY,
            'isActive' => $account->status === Account::STATUS_ACTIVE,
            'password_changed_at' => $account->password_changed_at,
        ];
    }

    public function actionChangePassword() {
        /** @var Account $account */
        $account = Yii::$app->user->identity;
        $model = new ChangePasswordForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->changePassword()) {
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
