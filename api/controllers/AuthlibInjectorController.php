<?php
declare(strict_types=1);

namespace api\controllers;

use api\filters\NginxCache;
use common\components\SkinsSystemApi;
use yii\helpers\ArrayHelper;
use yii\web\Controller as BaseController;

final class AuthlibInjectorController extends BaseController {

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

    public function actionIndex(SkinsSystemApi $skinsSystemApi): array {
        return [
            'meta' => [
                'serverName' => 'Ely.by',
                'implementationName' => 'Account Ely.by adapter for the authlib-injector library',
                'implementationVersion' => '1.0.0',
                'feature.no_mojang_namespace' => true,
                'feature.enable_profile_key' => true,
                'links' => [
                    'homepage' => 'https://ely.by',
                    'register' => 'https://account.ely.by/register',
                ],
            ],
            'skinDomains' => [
                'ely.by',
                '.ely.by',
            ],
            'signaturePublickey' => $skinsSystemApi->getSignatureVerificationKey(),
        ];
    }

}
