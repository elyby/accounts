<?php
declare(strict_types=1);

namespace api\controllers;

use api\filters\NginxCache;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\Controller as BaseController;

final class PublicKeysController extends BaseController {

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'nginxCache' => [
                'class' => NginxCache::class,
                'rules' => [
                    'index' => 3600, // 1h
                ],
            ],
        ]);
    }

    public function actionIndex(): array {
        return [
            'keys' => [
                [
                    'alg' => 'ES256', // Hardcoded for awhile since right now there is no way to find used algo
                    'pem' => Yii::$app->tokens->getPublicKey(),
                ],
            ],
        ];
    }

}
