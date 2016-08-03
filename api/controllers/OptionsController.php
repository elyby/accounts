<?php
namespace api\controllers;

use Yii;
use yii\helpers\ArrayHelper;

class OptionsController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['index'],
            ],
        ]);
    }

    public function verbs() {
        return [
            'index' => ['GET'],
        ];
    }

    public function actionIndex() {
        return [
            'reCaptchaPublicKey' => Yii::$app->reCaptcha->public,
        ];
    }

}
