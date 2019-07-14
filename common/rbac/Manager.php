<?php
namespace common\rbac;

use Yii;
use yii\rbac\PhpManager;

class Manager extends PhpManager {

    /**
     * In our application the permissions are given not to users but to tokens,
     * so we receive $accessToken here and extract all the assigned scopes from it.
     *
     * In Yii2, the mechanism of recursive permissions checking requires that the array with permissions
     * is indexed by the keys of these rights, so at the end we turn the array inside out.
     *
     * @param string $accessToken
     * @return string[]
     */
    public function getAssignments($accessToken): array {
        $identity = Yii::$app->user->findIdentityByAccessToken($accessToken);
        if ($identity === null) {
            return [];
        }

        /** @noinspection NullPointerExceptionInspection */
        $permissions = $identity->getAssignedPermissions();
        if (empty($permissions)) {
            return [];
        }

        return array_flip($permissions);
    }

}
