<?php
namespace codeception\common\unit\models;

use common\models\confirmations;
use common\models\EmailActivation;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\common\unit\TestCase;

class EmailActivationTest extends TestCase {

    public function _fixtures() {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testInstantiate() {
        $this->assertInstanceOf(confirmations\RegistrationConfirmation::class, EmailActivation::findOne([
            'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        ]));

        $this->assertInstanceOf(confirmations\ForgotPassword::class, EmailActivation::findOne([
            'type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY,
        ]));

        $this->assertInstanceOf(confirmations\CurrentEmailConfirmation::class, EmailActivation::findOne([
            'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
        ]));

        $this->assertInstanceOf(confirmations\NewEmailConfirmation::class, EmailActivation::findOne([
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]));
    }

}
