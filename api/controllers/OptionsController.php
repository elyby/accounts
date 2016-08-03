<?php
namespace api\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class OptionsController extends Controller {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['recaptcha-public-key'],
            ],
        ]);
    }

    public function verbs() {
        return [
            'recaptcha-public-key' => ['GET'],
        ];
    }

    public function actionRecaptchaPublicKey() {
        Yii::$app->response->format = Response::FORMAT_RAW;

        return Yii::$app->reCaptcha->public;
    }

}
