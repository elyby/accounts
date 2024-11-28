<?php
namespace api\tests\_pages;

class SignupRoute extends BasePage {

    public function register(array $registrationData): void
    {
        $this->getActor()->sendPOST('/api/signup', $registrationData);
    }

    public function sendRepeatMessage($email = ''): void
    {
        $this->getActor()->sendPOST('/api/signup/repeat-message', ['email' => $email]);
    }

    public function confirm($key = ''): void
    {
        $this->getActor()->sendPOST('/api/signup/confirm', [
            'key' => $key,
        ]);
    }

}
