<?php
namespace codeception\api\unit\models\authentication;

use api\models\authentication\ForgotPasswordForm;
use Codeception\Specify;
use common\models\EmailActivation;
use OTPHP\TOTP;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;

class ForgotPasswordFormTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testValidateLogin() {
        $this->specify('error.login_not_exist if login is invalid', function() {
            $model = new ForgotPasswordForm(['login' => 'unexist']);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->equals(['error.login_not_exist']);
        });

        $this->specify('empty errors if login is exists', function() {
            $model = new ForgotPasswordForm(['login' => $this->tester->grabFixture('accounts', 'admin')['username']]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->isEmpty();
        });
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
        $this->specify('error.account_not_activated if account is not confirmed', function() {
            $model = new ForgotPasswordForm([
                'login' => $this->tester->grabFixture('accounts', 'not-activated-account')['username'],
            ]);
            $model->validateActivity('login');
            expect($model->getErrors('login'))->equals(['error.account_not_activated']);
        });

        $this->specify('empty errors if login is exists', function() {
            $model = new ForgotPasswordForm([
                'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
            ]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testValidateFrequency() {
        $this->specify('error.account_not_activated if recently was message', function() {
            $model = $this->createModel([
                'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
                'key' => $this->tester->grabFixture('emailActivations', 'freshPasswordRecovery')['key'],
            ]);

            $model->validateFrequency('login');
            expect($model->getErrors('login'))->equals(['error.recently_sent_message']);
        });

        $this->specify('empty errors if email was sent a long time ago', function() {
            $model = $this->createModel([
                'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
                'key' => $this->tester->grabFixture('emailActivations', 'oldPasswordRecovery')['key'],
            ]);

            $model->validateFrequency('login');
            expect($model->getErrors('login'))->isEmpty();
        });

        $this->specify('empty errors if previous confirmation model not founded', function() {
            $model = $this->createModel([
                'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
                'key' => 'invalid-key',
            ]);

            $model->validateFrequency('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testForgotPassword() {
        $this->specify('successfully send message with restore password key', function() {
            $model = new ForgotPasswordForm(['login' => $this->tester->grabFixture('accounts', 'admin')['username']]);
            expect($model->forgotPassword())->true();
            expect($model->getEmailActivation())->notNull();
            $this->tester->canSeeEmailIsSent(1);
        });
    }

    public function testForgotPasswordResend() {
        $this->specify('successfully renew and send message with restore password key', function() {
            $fixture = $this->tester->grabFixture('accounts', 'account-with-expired-forgot-password-message');
            $model = new ForgotPasswordForm([
                'login' => $fixture['username'],
            ]);
            $callTime = time();
            expect($model->forgotPassword())->true();
            $emailActivation = $model->getEmailActivation();
            expect($emailActivation)->notNull();
            expect($emailActivation->created_at)->greaterOrEquals($callTime);
            $this->tester->canSeeEmailIsSent(1);
        });
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
