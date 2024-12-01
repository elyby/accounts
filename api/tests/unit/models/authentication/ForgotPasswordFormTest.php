<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\authentication\ForgotPasswordForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\EmailActivation;
use common\tasks\SendPasswordRecoveryEmail;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\EmailActivationFixture;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;

class ForgotPasswordFormTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Yii::$container->set(ReCaptchaValidator::class, new class($this->createMock(ClientInterface::class)) extends ReCaptchaValidator {
            public function validateValue($value): ?array {
                return null;
            }
        });
    }

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testValidateLogin(): void {
        $model = new ForgotPasswordForm(['login' => 'unexist']);
        $model->validateLogin('login');
        $this->assertSame(['error.login_not_exist'], $model->getErrors('login'), 'error.login_not_exist if login is invalid');

        $model = new ForgotPasswordForm(['login' => $this->tester->grabFixture('accounts', 'admin')['username']]);
        $model->validateLogin('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if login is exists');
    }

    public function testValidateActivity(): void {
        $model = new ForgotPasswordForm([
            'login' => $this->tester->grabFixture('accounts', 'not-activated-account')['username'],
        ]);
        $model->validateActivity('login');
        $this->assertSame(['error.account_not_activated'], $model->getErrors('login'), 'expected error if account is not confirmed');

        $model = new ForgotPasswordForm([
            'login' => $this->tester->grabFixture('accounts', 'admin')['username'],
        ]);
        $model->validateLogin('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if login is exists');
    }

    public function testValidateFrequency(): void {
        $model = $this->createModel();
        $model->login = $this->tester->grabFixture('accounts', 'admin')['username'];
        $model->method('getEmailActivation')->willReturn($this->tester->grabFixture('emailActivations', 'freshPasswordRecovery'));

        $model->validateFrequency('login');
        $this->assertSame(['error.recently_sent_message'], $model->getErrors('login'), 'error.account_not_activated if recently was message');

        $model = $this->createModel();
        $model->login = $this->tester->grabFixture('accounts', 'admin')['username'];
        $model->method('getEmailActivation')->willReturn($this->tester->grabFixture('emailActivations', 'oldPasswordRecovery'));
        $model->validateFrequency('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if email was sent a long time ago');

        $model = $this->createModel();
        $model->login = $this->tester->grabFixture('accounts', 'admin')['username'];
        $model->method('getEmailActivation')->willReturn(null);
        $model->validateFrequency('login');
        $this->assertEmpty($model->getErrors('login'), 'empty errors if previous confirmation model not founded');
    }

    public function testForgotPassword(): void {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new ForgotPasswordForm(['login' => $account->username]);
        $this->assertTrue($model->forgotPassword(), 'form should be successfully processed');
        $activation = $model->getEmailActivation();
        $this->assertInstanceOf(EmailActivation::class, $activation, 'getEmailActivation should return valid object instance');

        $this->assertTaskCreated($this->tester->grabLastQueuedJob(), $account, $activation);
    }

    public function testForgotPasswordResend(): void {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'account-with-expired-forgot-password-message');
        $model = new ForgotPasswordForm(['login' => $account->username]);
        $callTime = time();
        $this->assertTrue($model->forgotPassword(), 'form should be successfully processed');
        $emailActivation = $model->getEmailActivation();
        $this->assertInstanceOf(EmailActivation::class, $emailActivation);
        $this->assertGreaterThanOrEqual($callTime, $emailActivation->created_at);

        $this->assertTaskCreated($this->tester->grabLastQueuedJob(), $account, $emailActivation);
    }

    /**
     * @param \yii\queue\JobInterface $job
     * @param Account $account
     * @param EmailActivation $activation
     */
    private function assertTaskCreated($job, Account $account, EmailActivation $activation): void {
        $this->assertInstanceOf(SendPasswordRecoveryEmail::class, $job);
        /** @var SendPasswordRecoveryEmail $job */
        $this->assertSame($account->username, $job->username);
        $this->assertSame($account->email, $job->email);
        $this->assertSame($account->lang, $job->locale);
        $this->assertSame($activation->key, $job->code);
        $this->assertSame('http://localhost/recover-password/' . $activation->key, $job->link);
    }

    private function createModel(): ForgotPasswordForm&MockObject {
        return $this->createPartialMock(ForgotPasswordForm::class, ['getEmailActivation']);
    }

}
