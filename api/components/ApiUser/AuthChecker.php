<?php
namespace api\components\ApiUser;

use Yii;
use yii\rbac\CheckAccessInterface;

class AuthChecker implements CheckAccessInterface {

    /**
     * @inheritdoc
     */
    public function checkAccess($token, $permissionName, $params = []) : bool {
        $accessToken = Yii::$app->oauth->getAuthServer()->getAccessTokenStorage()->get($token);
        if ($accessToken === null) {
            return false;
        }

        return $accessToken->hasScope($permissionName);
    }

}
