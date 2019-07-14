<?php
declare(strict_types=1);

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
    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface;

    /**
     * This method is used to obtain a token to which scopes are attached.
     * Our permissions are attached to tokens itself, so we return its id.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * The method returns an account that is attached to the current token.
     * But it's possible that the token was issued without binding to the account,
     * so you should handle it.
     *
     * @return Account|null
     */
    public function getAccount(): ?Account;

    /**
     * @return string[]
     */
    public function getAssignedPermissions(): array;

}
