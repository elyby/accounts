<?php
namespace common\models;

use common\components\Annotations\Reader;
use ReflectionClass;
use Yii;
use yii\helpers\ArrayHelper;

class OauthScope {

    const OFFLINE_ACCESS = 'offline_access';
    const MINECRAFT_SERVER_SESSION = 'minecraft_server_session';
    const ACCOUNT_INFO = 'account_info';
    const ACCOUNT_EMAIL = 'account_email';

    /** @internal */
    const ACCOUNT_BLOCK = 'account_block';

    public static function getScopes(): array {
        return ArrayHelper::getColumn(static::queryScopes(), 'value');
    }

    public static function getPublicScopes(): array {
        return ArrayHelper::getColumn(array_filter(static::queryScopes(), function($value) {
            return !isset($value['internal']);
        }), 'value');
    }

    public static function getInternalScopes(): array {
        return ArrayHelper::getColumn(array_filter(static::queryScopes(), function($value) {
            return isset($value['internal']);
        }), 'value');
    }

    private static function queryScopes(): array {
        $cacheKey = 'oauth-scopes-list';
        $scopes = false;
        if ($scopes === false) {
            $scopes = [];
            $reflection = new ReflectionClass(static::class);
            $constants = $reflection->getConstants();
            $reader = Reader::createFromDefaults();
            foreach ($constants as $constName => $value) {
                $annotations = $reader->getConstantAnnotations(static::class, $constName);
                $isInternal = $annotations->get('internal', false);
                $keyValue = [
                    'value' => $value,
                ];
                if ($isInternal) {
                    $keyValue['internal'] = true;
                }
                $scopes[$constName] = $keyValue;
            }

            Yii::$app->cache->set($cacheKey, $scopes, 3600);
        }

        return $scopes;
    }

}
