<?php
namespace api\modules\internal\controllers;

use api\controllers\Controller;
use api\rbac\Permissions as P;
use common\models\Account;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class AccountsController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['info'],
                        'allow' => true,
                        'roles' => [P::OBTAIN_EXTENDED_ACCOUNT_INFO],
                        'roleParams' => function() {
                            return [
                                'accountId' => 0,
                            ];
                        },
                    ],
                ],
            ],
        ]);
    }

    public function verbs(): array {
        return [
            'info' => ['GET'],
        ];
    }

    public function actionInfo(int $id = null, string $username = null, string $uuid = null) {
        if ($id !== null) {
            $account = Account::findOne($id);
        } elseif ($username !== null) {
            $account = Account::findOne(['username' => $username]);
        } elseif ($uuid !== null) {
            $account = Account::findOne(['uuid' => $uuid]);
        } else {
            throw new BadRequestHttpException('One of the required get params must be presented.');
        }

        if ($account === null) {
            throw new NotFoundHttpException('User by provided param not found.');
        }

        return [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'email' => $account->email,
            'username' => $account->username,
        ];
    }

}
