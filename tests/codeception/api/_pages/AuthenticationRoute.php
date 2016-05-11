<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class AuthenticationRoute extends BasePage {

    public function login($login = '', $password = '') {
        $this->route = ['authentication/login'];
        $this->actor->sendPOST($this->getUrl(), [
            'login' => $login,
            'password' => $password,
        ]);
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

}
