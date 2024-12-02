<?php
declare(strict_types=1);

namespace api\rbac;

use Yii;
use yii\rbac\Assignment;
use yii\rbac\PhpManager;

class Manager extends PhpManager {

    /**
     * In our application the permissions are given not to users itself, but to tokens,
     * so we extract them from the extended identity interface.
     *
     * In Yii2, the mechanism of recursive permissions checking requires that the array
     * with permissions must be indexed by the keys of these permissions.
     *
     * @return array<string, \yii\rbac\Assignment>
     */
    public function getAssignments($userId): array {
        $identity = Yii::$app->user->getIdentity();
        if ($identity === null) {
            return [];
        }

        $rawPermissions = $identity->getAssignedPermissions();
        $result = [];
        foreach ($rawPermissions as $name) {
            $result[$name] = new Assignment(['roleName' => $name]);
        }

        return $result;
    }

}
