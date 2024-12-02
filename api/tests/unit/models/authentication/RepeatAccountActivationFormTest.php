<?php
declare(strict_types=1);

namespace api\tests\unit\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\authentication\RepeatAccountActivationForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\confirmations\RegistrationConfirmation;
use common\tasks\SendRegistrationEmail;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\EmailActivationFixture;
use GuzzleHttp\ClientInterface;
use Yii;

class RepeatAccountActivationFormTest extends TestCase {

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
            'activations' => EmailActivationFixture::class,
        ];
    }

    public function testValidateEmailForAccount(): void {
        $model = $this->createWithAccount(null);
        $model->validateEmailForAccount('email');
        $this->assertSame(['error.email_not_found'], $model->getErrors('email'));

        $account = new Account();
        $account->status = Account::STATUS_ACTIVE;
        $model = $this->createWithAccount($account);
        $model->validateEmailForAccount('email');
        $this->assertSame(['error.account_already_activated'], $model->getErrors('email'));

        $account = new Account();
        $account->status = Account::STATUS_REGISTERED;
        $model = $this->createWithAccount($account);
        $model->validateEmailForAccount('email');
        $this->assertEmpty($model->getErrors('email'));
    }

    public function testValidateExistsActivation(): void {
        $activation = new RegistrationConfirmation();
        $activation->created_at = time() - 10;
        $model = $this->createWithActivation($activation);
        $model->validateExistsActivation('email');
        $this->assertSame(['error.recently_sent_message'], $model->getErrors('email'));

        $activation = new RegistrationConfirmation();
        $activation->created_at = time() - 60 * 60 * 24;
        $model = $this->createWithActivation($activation);
        $model->validateExistsActivation('email');
        $this->assertEmpty($model->getErrors('email'));
    }

    public function testSendRepeatMessage(): void {
        $model = new RepeatAccountActivationForm();
        $this->assertFalse($model->sendRepeatMessage(), 'no magic if we don\'t pass validation');
        $this->assertEmpty($this->tester->grabQueueJobs());

        /** @var \common\models\Account $account */
        $account = $this->tester->grabFixture('accounts', 'not-activated-account-with-expired-message');
        $model = new RepeatAccountActivationForm(['email' => $account->email]);
        $this->assertTrue($model->sendRepeatMessage());
        $activation = $model->getActivation();
        $this->assertNotNull($activation);
        /** @var SendRegistrationEmail $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(SendRegistrationEmail::class, $job);
        $this->assertSame($account->username, $job->username);
        $this->assertSame($account->email, $job->email);
        $this->assertSame($account->lang, $job->locale);
        $this->assertSame($activation->key, $job->code);
        $this->assertSame('http://localhost/activation/' . $activation->key, $job->link);
    }

    private function createWithAccount(?Account $account): RepeatAccountActivationForm {
        $model = $this->createPartialMock(RepeatAccountActivationForm::class, ['getAccount']);
        $model->method('getAccount')->willReturn($account);

        return $model;
    }

    private function createWithActivation(?RegistrationConfirmation $activation): RepeatAccountActivationForm {
        $model = $this->createPartialMock(RepeatAccountActivationForm::class, ['getActivation']);
        $model->method('getActivation')->willReturn($activation);

        return $model;
    }

}
