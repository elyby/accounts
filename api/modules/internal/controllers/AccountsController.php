<?php
namespace api\modules\internal\controllers;

use api\components\ApiUser\AccessControl;
use api\controllers\Controller;
use api\modules\internal\models\BanForm;
use common\models\Account;
use common\models\OauthScope as S;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class AccountsController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'user' => Yii::$app->apiUser,
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['ban'],
                        'allow' => true,
                        'roles' => [S::ACCOUNT_BLOCK],
                    ],
                ],
            ],
        ]);
    }

    public function actionBan(int $accountId) {
        $account = $this->findAccount($accountId);
        $model = new BanForm($account);
        $model->load(Yii::$app->request->post());
        if (!$model->ban()) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return [
            'success' => true,
        ];
    }

    private function findAccount(int $accountId): Account {
        $account = Account::findOne($accountId);
        if ($account === null) {
            throw new NotFoundHttpException();
        }

        return $account;
    }

}
