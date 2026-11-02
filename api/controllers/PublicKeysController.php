<?php
declare(strict_types=1);

namespace api\controllers;

use api\filters\NginxCache;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webmozart\Assert\Assert;
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
            'keys' => array_map(fn(array $jwk): array => [
                'alg' => $jwk['alg'],
                'kid' => $jwk['kid'],
                'pem' => self::convertJwkToPem($jwk),
            ], Yii::$app->tokens->getJwks()['keys']),
        ];
    }

    /**
     * @param array<string, string> $jwk
     */
    private static function convertJwkToPem(array $jwk): string {
        Assert::same($jwk['kty'] ?? null, 'EC');
        Assert::same($jwk['crv'] ?? null, 'P-256');

        $key = openssl_pkey_new([
            'ec' => [
                'curve_name' => 'prime256v1',
                'x' => Base64UrlSafe::decode($jwk['x']),
                'y' => Base64UrlSafe::decode($jwk['y']),
            ],
        ]);
        Assert::notFalse($key);
        $details = openssl_pkey_get_details($key);
        Assert::isArray($details);

        return $details['key'];
    }

}
