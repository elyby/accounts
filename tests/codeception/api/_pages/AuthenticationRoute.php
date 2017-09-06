<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class AuthenticationRoute extends BasePage {

    /**
     * @param string           $login
     * @param string           $password
     * @param string|bool|null $rememberMeOrToken
     * @param bool             $rememberMe
     */
    public function login($login = '', $password = '', $rememberMeOrToken = null, $rememberMe = false) {
        $this->route = ['authentication/login'];
        $params = [
            'login' => $login,
            'password' => $password,
        ];

        if ((is_bool($rememberMeOrToken) && $rememberMeOrToken) || $rememberMe) {
            $params['rememberMe'] = 1;
        } elseif ($rememberMeOrToken !== null) {
            $params['totp'] = $rememberMeOrToken;
        }

        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function logout() {
        $this->route = ['authentication/logout'];
        $this->actor->sendPOST($this->getUrl());
    }

    public function forgotPassword($login = null, $token = null) {
        $this->route = ['authentication/forgot-password'];
        $this->actor->sendPOST($this->getUrl(), [
            'login' => $login,
            'totp' => $token,
        ]);
    }

    public function recoverPassword($key = null, $newPassword = null, $newRePassword = null) {
        $this->route = ['authentication/recover-password'];
        $this->actor->sendPOST($this->getUrl(), [
            'key' => $key,
            'newPassword' => $newPassword,
            'newRePassword' => $newRePassword,
        ]);
    }

    public function refreshToken($refreshToken = null) {
        $this->route = ['authentication/refresh-token'];
        $this->actor->sendPOST($this->getUrl(), [
            'refresh_token' => $refreshToken,
        ]);
    }

}
