<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * Represents loging page
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class LoginRoute extends BasePage {

    public $route = 'login/authentication/login-info';

    public function login($email, $password) {
        $this->actor->sendPOST($this->getUrl(), [
            'email' => $email,
            'password' => $password,
        ]);
    }

}
