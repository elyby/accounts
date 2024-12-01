<?php
namespace api\controllers;

use api\filters\NginxCache;
use Yii;
use yii\helpers\ArrayHelper;

class OptionsController extends Controller {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'authenticator' => [
                'except' => ['index'],
            ],
            'nginxCache' => [
                'class' => NginxCache::class,
                'rules' => [
                    'index' => 3600, // 1h
                ],
            ],
        ]);
    }

    public function verbs() {
        return [
            'index' => ['GET'],
        ];
    }

    public function actionIndex(): array {
        return [
            'reCaptchaPublicKey' => Yii::$app->reCaptcha->public,
        ];
    }

}
