<?php
namespace api\controllers;

use common\models\OauthScope;
use Yii;
use yii\filters\AccessControl;
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
                        'roles' => ['@'],
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
            'registeredAt' => $account->created_at,
            'profileLink' => $account->getProfileLink(),
            'preferredLanguage' => $account->lang,
        ];

        if (Yii::$app->apiUser->can(OauthScope::ACCOUNT_EMAIL)) {
            $response['email'] = $account->email;
        }

        return $response;
    }

}
