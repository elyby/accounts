<?php
declare(strict_types=1);

namespace api\components\User;

use yii\web\UnauthorizedHttpException;

class IdentityFactory {

    /**
     * @param string $token
     * @param string $type
     *
     * @return IdentityInterface
     * @throws UnauthorizedHttpException
     */
    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        if (!empty($token)) {
            if (mb_strlen($token) === 40) {
                return LegacyOAuth2Identity::findIdentityByAccessToken($token, $type);
            }

            if (substr_count($token, '.') === 2) {
                return JwtIdentity::findIdentityByAccessToken($token, $type);
            }
        }

        throw new UnauthorizedHttpException('Incorrect token');
    }

}
