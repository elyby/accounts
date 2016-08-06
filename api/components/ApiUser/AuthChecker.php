<?php
namespace api\components\ApiUser;

use common\models\OauthAccessToken;
use yii\rbac\CheckAccessInterface;

class AuthChecker implements CheckAccessInterface {

    /**
     * @inheritdoc
     */
    public function checkAccess($token, $permissionName, $params = []) : bool {
        /** @var OauthAccessToken|null $accessToken */
        $accessToken = OauthAccessToken::findOne($token);
        if ($accessToken === null) {
            return false;
        }

        return $accessToken->getScopes()->exists($permissionName);
    }

}
