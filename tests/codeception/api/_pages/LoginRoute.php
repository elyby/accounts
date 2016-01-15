<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class LoginRoute extends BasePage {

    public $route = ['authentication/login'];

    public function login($login = '', $password = '') {
        $this->actor->sendPOST($this->getUrl(), [
            'login' => $login,
            'password' => $password,
        ]);
    }

}
