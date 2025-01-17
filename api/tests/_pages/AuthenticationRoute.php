<?php
declare(strict_types=1);

namespace api\tests\_pages;

final class AuthenticationRoute extends BasePage {

    public function login(string $login = '', string $password = '', bool|string|null $rememberMeOrToken = null, bool $rememberMe = false): void {
        $params = [
            'login' => $login,
            'password' => $password,
        ];

        if ((is_bool($rememberMeOrToken) && $rememberMeOrToken) || $rememberMe) {
            $params['rememberMe'] = 1;
        } elseif ($rememberMeOrToken !== null) {
            $params['totp'] = $rememberMeOrToken;
        }

        $this->getActor()->sendPOST('/api/authentication/login', $params);
    }

    public function forgotPassword($login = null, $token = null): void {
        $this->getActor()->sendPOST('/api/authentication/forgot-password', [
            'login' => $login,
            'totp' => $token,
        ]);
    }

    public function recoverPassword($key = null, $newPassword = null, $newRePassword = null): void {
        $this->getActor()->sendPOST('/api/authentication/recover-password', [
            'key' => $key,
            'newPassword' => $newPassword,
            'newRePassword' => $newRePassword,
        ]);
    }

    public function refreshToken($refreshToken = null): void {
        $this->getActor()->sendPOST('/api/authentication/refresh-token', [
            'refresh_token' => $refreshToken,
        ]);
    }

}
