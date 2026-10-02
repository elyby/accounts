<?php
namespace api\tests\_pages;

use api\components\Captcha\Providers\ReCaptchaEnterprise;
use api\tests\_support\Captcha\FakeCaptchaProvider;

class SignupRoute extends BasePage {

    public function register(array $registrationData): void {
        $this->getActor()->sendPOST('/api/signup', $registrationData + ['captcha' => FakeCaptchaProvider::VALID, 'captchaType' => ReCaptchaEnterprise::NAME]);
    }

    public function sendRepeatMessage($email = ''): void {
        $this->getActor()->sendPOST('/api/signup/repeat-message', ['email' => $email, 'captcha' => FakeCaptchaProvider::VALID, 'captchaType' => ReCaptchaEnterprise::NAME]);
    }

    public function confirm($key = ''): void {
        $this->getActor()->sendPOST('/api/signup/confirm', [
            'key' => $key,
        ]);
    }

}
