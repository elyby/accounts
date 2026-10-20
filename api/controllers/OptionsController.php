<?php
namespace api\controllers;

use api\components\Captcha\CaptchaRegistry;
use api\components\Captcha\CaptchaValidator;
use api\components\Captcha\Providers\ProviderInterface;
use api\filters\NginxCache;
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

    public function actionIndex(
        CaptchaRegistry $captchaRegistry,
    ): array {
        return [
            'captcha' => $captchaRegistry->getPublicParams(),
        ];
    }

}
