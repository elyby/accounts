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

}
