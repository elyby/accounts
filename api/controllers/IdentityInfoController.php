<?php
namespace api\controllers;

use api\components\ApiUser\AccessControl;
use common\models\OauthScope as S;
use Yii;
use yii\helpers\ArrayHelper;

class IdentityInfoController extends ApiController {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => [S::ACCOUNT_INFO],
                    ],
                ],
            ],
        ]);
    }

    public function actionIndex() {
        $account = Yii::$app->apiUser->getIdentity()->getAccount();
        $response = [
            'id' => $account->id,
            'uuid' => $account->uuid,
            'username' => $account->username,
            'registeredAt' => $account->created_at,
            'profileLink' => $account->getProfileLink(),
            'preferredLanguage' => $account->lang,
        ];

        if (Yii::$app->apiUser->can(S::ACCOUNT_EMAIL)) {
            $response['email'] = $account->email;
        }

        return $response;
    }

}
