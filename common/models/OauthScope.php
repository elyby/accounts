<?php
namespace common\models;

use common\components\Annotations\Reader;
use ReflectionClass;
use Yii;

class OauthScope {

    /**
     * @owner user
     */
    const OFFLINE_ACCESS = 'offline_access';
    /**
     * @owner user
     */
    const MINECRAFT_SERVER_SESSION = 'minecraft_server_session';
    /**
     * @owner user
     */
    const ACCOUNT_INFO = 'account_info';
    /**
     * @owner user
     */
    const ACCOUNT_EMAIL = 'account_email';
    /**
     * @internal
     * @owner machine
     */
    const ACCOUNT_BLOCK = 'account_block';

    public static function find(): OauthScopeQuery {
        return new OauthScopeQuery(static::queryScopes());
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
                $owner = $annotations->get('owner', 'user');
                $keyValue = [
                    'value' => $value,
                    'internal' => $isInternal,
                    'owner' => $owner,
                ];
                $scopes[$constName] = $keyValue;
            }

            Yii::$app->cache->set($cacheKey, $scopes, 3600);
        }

        return $scopes;
    }

}
