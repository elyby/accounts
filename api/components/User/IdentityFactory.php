<?php
declare(strict_types=1);

namespace api\components\User;

use yii\web\UnauthorizedHttpException;

class IdentityFactory {

    /**
     * @throws UnauthorizedHttpException
     * @return IdentityInterface
     */
    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        if (empty($token)) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        if (substr_count($token, '.') === 2) {
            return JwtIdentity::findIdentityByAccessToken($token, $type);
        }

        return OAuth2Identity::findIdentityByAccessToken($token, $type);
    }

}
