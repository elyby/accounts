<?php
namespace codeception\api\unit\models;

use api\models\ForgotPasswordForm;
use Codeception\Specify;
use common\models\EmailActivation;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use Yii;

/**
 * @property array $accounts
 * @property array $emailActivations
 */
class ForgotPasswordFormTest extends DbTestCase {
    use Specify;

    protected function setUp() {
        parent::setUp();
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;
        $mailer->fileTransportCallback = function () {
            return 'testing_message.eml';
        };
    }

    protected function tearDown() {
        if (file_exists($this->getMessageFile())) {
            unlink($this->getMessageFile());
        }

        parent::tearDown();
    }

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
            'emailActivations' => [
                'class' => EmailActivationFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/email-activations.php',
            ],
        ];
    }

    public function testValidateAccount() {
        $this->specify('error.login_not_exist if login is invalid', function() {
            $model = new ForgotPasswordForm(['login' => 'unexist']);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->equals(['error.login_not_exist']);
        });

        $this->specify('empty errors if login is exists', function() {
            $model = new ForgotPasswordForm(['login' => $this->accounts['admin']['username']]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testValidateActivity() {
        $this->specify('error.account_not_activated if account is not confirmed', function() {
            $model = new ForgotPasswordForm(['login' => $this->accounts['not-activated-account']['username']]);
            $model->validateActivity('login');
            expect($model->getErrors('login'))->equals(['error.account_not_activated']);
        });

        $this->specify('empty errors if login is exists', function() {
            $model = new ForgotPasswordForm(['login' => $this->accounts['admin']['username']]);
            $model->validateLogin('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testValidateFrequency() {
        $this->specify('error.account_not_activated if recently was message', function() {
            $model = new DummyForgotPasswordForm([
                'login' => $this->accounts['admin']['username'],
                'key' => $this->emailActivations['freshPasswordRecovery']['key'],
            ]);

            $model->validateFrequency('login');
            expect($model->getErrors('login'))->equals(['error.email_frequency']);
        });

        $this->specify('empty errors if email was sent a long time ago', function() {
            $model = new DummyForgotPasswordForm([
                'login' => $this->accounts['admin']['username'],
                'key' => $this->emailActivations['oldPasswordRecovery']['key'],
            ]);

            $model->validateFrequency('login');
            expect($model->getErrors('login'))->isEmpty();
        });

        $this->specify('empty errors if previous confirmation model not founded', function() {
            $model = new DummyForgotPasswordForm([
                'login' => $this->accounts['admin']['username'],
                'key' => 'invalid-key',
            ]);

            $model->validateFrequency('login');
            expect($model->getErrors('login'))->isEmpty();
        });
    }

    public function testForgotPassword() {
        $this->specify('successfully send message with restore password key', function() {
            $model = new ForgotPasswordForm(['login' => $this->accounts['admin']['username']]);
            expect($model->forgotPassword())->true();
            expect($model->getEmailActivation())->notNull();
            expect_file($this->getMessageFile())->exists();
        });
    }

    public function testForgotPasswordResend() {
        $this->specify('successfully renew and send message with restore password key', function() {
            $model = new ForgotPasswordForm([
                'login' => $this->accounts['account-with-expired-forgot-password-message']['username'],
            ]);
            $callTime = time();
            expect($model->forgotPassword())->true();
            $emailActivation = $model->getEmailActivation();
            expect($emailActivation)->notNull();
            expect($emailActivation->created_at)->greaterOrEquals($callTime);
            expect_file($this->getMessageFile())->exists();
        });
    }

    private function getMessageFile() {
        /** @var \yii\swiftmailer\Mailer $mailer */
        $mailer = Yii::$app->mailer;

        return Yii::getAlias($mailer->fileTransportPath) . '/testing_message.eml';
    }

}

class DummyForgotPasswordForm extends ForgotPasswordForm {

    public $key;

    public function getEmailActivation() {
        return EmailActivation::findOne($this->key);
    }

}
