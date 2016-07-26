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
            'emailActivations' => EmailActivationFixture::class,
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

    public function testBeforeSave() {
        $this->specify('method should generate value for key field if it empty', function() {
            $model = new EmailActivation();
            $model->beforeSave(true);
            expect($model->key)->notNull();
        });

        $this->specify('method should repeat code generation if code duplicate with exists', function() {
            /** @var EmailActivation|\PHPUnit_Framework_MockObject_MockObject $model */
            $model = $this->getMockBuilder(EmailActivation::class)
                ->setMethods(['generateKey', 'isKeyExists'])
                ->getMock();

            $model->expects($this->exactly(3))
                ->method('generateKey')
                ->will($this->onConsecutiveCalls('1', '2', '3'));

            $model->expects($this->exactly(3))
                  ->method('isKeyExists')
                  ->will($this->onConsecutiveCalls(true, true, false));

            $model->beforeSave(true);
            expect($model->key)->equals('3');
        });
    }

}
