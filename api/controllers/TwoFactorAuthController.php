<?php
namespace api\controllers;

use api\filters\ActiveUserRule;
use api\models\profile\TwoFactorAuthForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;

class TwoFactorAuthController extends Controller {

    public $defaultAction = 'credentials';

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'class' => ActiveUserRule::class,
                    ],
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'credentials' => ['GET'],
            'activate' => ['POST'],
            'disable' => ['DELETE'],
        ];
    }

    public function actionCredentials() {
        $account = Yii::$app->user->identity;
        $model = new TwoFactorAuthForm($account);

        return $model->getCredentials();
    }

    public function actionActivate() {
        $account = Yii::$app->user->identity;
        $model = new TwoFactorAuthForm($account, ['scenario' => TwoFactorAuthForm::SCENARIO_ACTIVATE]);
        $model->load(Yii::$app->request->post());
        if (!$model->activate()) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return [
            'success' => true,
        ];
    }

    public function actionDisable() {
        $account = Yii::$app->user->identity;
        $model = new TwoFactorAuthForm($account, ['scenario' => TwoFactorAuthForm::SCENARIO_DISABLE]);
        $model->load(Yii::$app->request->getBodyParams());
        if (!$model->disable()) {
            return [
                'success' => false,
                'errors' => $model->getFirstErrors(),
            ];
        }

        return [
            'success' => true,
        ];
    }

}