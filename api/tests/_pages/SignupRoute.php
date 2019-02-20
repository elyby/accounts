<?php
namespace api\tests\_pages;

class SignupRoute extends BasePage {

    public function register(array $registrationData) {
        $this->getActor()->sendPOST('/api/signup', $registrationData);
    }

    public function sendRepeatMessage($email = '') {
        $this->getActor()->sendPOST('/api/signup/repeat-message', ['email' => $email]);
    }

    public function confirm($key = '') {
        $this->getActor()->sendPOST('/api/signup/confirm', [
            'key' => $key,
        ]);
    }

}
