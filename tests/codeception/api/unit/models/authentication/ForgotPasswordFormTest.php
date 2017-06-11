<?php
namespace codeception\api\unit\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\authentication\ForgotPasswordForm;
use Codeception\Specify;
use common\models\EmailActivation;
use GuzzleHttp\ClientInterface;
use OTPHP\TOTP;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

class ForgotPasswordFormTest extends TestCase {
    use Specify;

    public function setUp() {
        parent::setUp();
        Yii::$container->set(ReCaptchaValidator::class, new class(mock(ClientInterface::class)) extends ReCaptchaValidator {
            public function validateValue($value) {
                return null;
            }
        });
    }

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testValidateLogin() {
        $model = new ForgotPasswordForm(['login' => 'unexist']);
        $model->validateLogin('login');
        $this->assertEquals(['error.login_not_exist'], $model->getErrors('login'), 'error.login_not_exist if login is invalid');

        $model = new ForgotPasswordForm(['login' => $this->tester->grabFixture('accounts', 'admin')['username']]);
        $model->validateLogin('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if login is exists');
    }

    public function testValidateTotpToken() {
        $model = new ForgotPasswordForm();
        $model->login = 'AccountWithEnabledOtp';
        $model->token = '123456';
        $model->validateTotpToken('token');
        $this->assertEquals(['error.token_incorrect'], $model->getErrors('token'));

        $totp = new TOTP(null, 'secret-secret-secret');
        $model = new ForgotPasswordForm();
        $model->login = 'AccountWithEnabledOtp';
        $model->token = $totp->now();
        $model->validateTotpToken('token');
        $this->assertEmpty($model->getErrors('token'));
    }

    public function testValidateActivity() {
        $model = new ForgotPasswordForm([
            'login' => $this->tester->grabFixture('accounts', 'not-activated-account')['username'],
        ]);
        $model->validateActivity('login');
        $this->assertEquals(['error.account_not_activated'], $model->getErrors('login'), 'expected error if account is not confirmed');

        $model = new ForgotPasswordForm([
            'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
        ]);
        $model->validateLogin('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if login is exists');
    }

    public function testValidateFrequency() {
        $model = $this->createModel([
            'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
            'key' => $this->tester->grabFixture('emailActivations', 'freshPasswordRecovery')['key'],
        ]);
        $model->validateFrequency('login');
        $this->assertEquals(['error.recently_sent_message'], $model->getErrors('login'), 'error.account_not_activated if recently was message');

        $model = $this->createModel([
            'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
            'key' => $this->tester->grabFixture('emailActivations', 'oldPasswordRecovery')['key'],
        ]);
        $model->validateFrequency('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if email was sent a long time ago');

        $model = $this->createModel([
            'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
            'key' => 'invalid-key',
        ]);
        $model->validateFrequency('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if previous confirmation model not founded');
    }

    public function testForgotPassword() {
        $model = new ForgotPasswordForm(['login' => $this->tester->grabFixture('accounts', 'admin')['username']]);
        $this->assertTrue($model->forgotPassword(), 'form should be successfully processed');
        $activation = $model->getEmailActivation();
        $this->assertInstanceOf(EmailActivation::class, $activation, 'getEmailActivation should return valid object instance');
        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\swiftmailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $body = $email->getSwiftMessage()->getBody();
        $this->assertContains($activation->key, $body);
        $this->assertContains('/recover-password/' . $activation->key, $body);
    }

    public function testForgotPasswordResend() {
        $fixture = $this->tester->grabFixture('accounts', 'account-with-expired-forgot-password-message');
        $model = new ForgotPasswordForm([
            'login' => $fixture['username'],
        ]);
        $callTime = time();
        $this->assertTrue($model->forgotPassword(), 'form should be successfully processed');
        $emailActivation = $model->getEmailActivation();
        $this->assertInstanceOf(EmailActivation::class, $emailActivation);
        $this->assertGreaterThanOrEqual($callTime, $emailActivation->created_at);
        $this->tester->canSeeEmailIsSent(1);
    }

    /**
     * @param array $params
     * @return ForgotPasswordForm
     */
    private function createModel(array $params = []) {
        return new class($params) extends ForgotPasswordForm {
            public $key;

            public function getEmailActivation() {
                return EmailActivation::findOne($this->key);
            }
        };
    }

}
