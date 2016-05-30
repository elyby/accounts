<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class AuthenticationRoute extends BasePage {

    public function login($login = '', $password = '', $rememberMe = false) {
        $this->route = ['authentication/login'];
        $params = [
            'login' => $login,
            'password' => $password,
        ];

        if ($rememberMe) {
            $params['rememberMe'] = 1;
        }

        $this->actor->sendPOST($this->getUrl(), $params);
    }

    public function forgotPassword($login = '') {
        $this->route = ['authentication/forgot-password'];
        $this->actor->sendPOST($this->getUrl(), [
            'login' => $login,
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
