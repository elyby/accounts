<?php
namespace api\components\User;

use common\models\Account;

interface IdentityInterface extends \yii\web\IdentityInterface {

    /**
     * @param string $token
     * @param string $type
     *
     * @throws \yii\web\UnauthorizedHttpException
     * @return IdentityInterface
     */
    public static function findIdentityByAccessToken($token, $type = null): self;

    /**
     * Этот метод используется для получения токена, к которому привязаны права.
     * У нас права привязываются к токенам, так что возвращаем именно его id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Метод возвращает аккаунт, который привязан к текущему токену.
     * Но не исключено, что токен был выдан и без привязки к аккаунту, так что
     * следует это учитывать.
     *
     * @return Account|null
     */
    public function getAccount(): ?Account;

    /**
     * @return string[]
     */
    public function getAssignedPermissions(): array;

}
