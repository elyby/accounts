<?php
namespace tests\codeception\api\_pages;

class SignupRoute extends BasePage {

    public function register(array $registrationData) {
        $this->getActor()->sendPOST('/signup', $registrationData);
    }

    public function sendRepeatMessage($email = '') {
        $this->getActor()->sendPOST('/signup/repeat-message', ['email' => $email]);
    }

    public function confirm($key = '') {
        $this->getActor()->sendPOST('/signup/confirm', [
            'key' => $key,
        ]);
    }

}
