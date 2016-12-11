<?php
namespace api\modules\internal\controllers;

use api\components\ApiUser\AccessControl;
use api\controllers\Controller;
use common\models\OauthScope as S;
use yii\helpers\ArrayHelper;

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

    }

}
