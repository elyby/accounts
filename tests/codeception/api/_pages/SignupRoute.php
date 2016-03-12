<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class SignupRoute extends BasePage {

    public function register(array $registrationData) {
        $this->route = ['signup/index'];
        $this->actor->sendPOST($this->getUrl(), $registrationData);
    }

    public function sendNewMessage($email = '') {
        $this->route = ['signup/new-message'];
        $this->actor->sendPOST($this->getUrl(), ['email' => $email]);
    }

    public function confirm($key = '') {
        $this->route = ['signup/confirm'];
        $this->actor->sendPOST($this->getUrl(), [
            'key' => $key,
        ]);
    }

}
