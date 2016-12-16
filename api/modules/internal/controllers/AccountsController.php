<?php
namespace api\modules\internal\controllers;

use api\components\ApiUser\AccessControl;
use api\controllers\Controller;
use api\modules\internal\models\BlockForm;
use common\models\Account;
use common\models\OauthScope as S;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class AccountsController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['block'],
                        'allow' => true,
                        'roles' => [S::ACCOUNT_BLOCK],
                    ],
                ],
            ],
        ]);
    }

    public function actionBlock(int $accountId) {
        $account = $this->findAccount($accountId);
        $model = new BlockForm($account);
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
