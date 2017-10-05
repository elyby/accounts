<?php
namespace common\rbac;

use Yii;
use yii\rbac\PhpManager;

class Manager extends PhpManager {

    /**
     * В нашем приложении права выдаются не пользователям, а токенам, так что ожидаем
     * здесь $accessToken и извлекаем из него все присвоенные права.
     *
     * По каким-то причинам, в Yii механизм рекурсивной проверки прав требует, чтобы
     * массив с правами был проиндексирован по ключам этих самых прав, так что в
     * конце выворачиваем массив наизнанку.
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
