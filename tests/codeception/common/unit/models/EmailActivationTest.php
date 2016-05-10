<?php
namespace codeception\common\unit\models;

use Codeception\Specify;
use common\models\confirmations\ForgotPassword;
use common\models\confirmations\RegistrationConfirmation;
use common\models\EmailActivation;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\console\unit\DbTestCase;

class EmailActivationTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'emailActivations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    public function testInstantiate() {
        $this->specify('return valid model type', function() {
            expect(EmailActivation::findOne([
                'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
            ]))->isInstanceOf(RegistrationConfirmation::class);
            expect(EmailActivation::findOne([
                'type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY,
            ]))->isInstanceOf(ForgotPassword::class);
        });
    }

}
