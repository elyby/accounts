<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\SendNewEmailVerificationForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use common\tasks\SendNewEmailConfirmation;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\EmailActivationFixture;
use yii\validators\EmailValidator;

class SendNewEmailVerificationFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testCreateCode() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new SendNewEmailVerificationForm($account);
        $model->email = 'my-new-email@ely.by';
        $activationModel = $model->createCode();
        $this->assertInstanceOf(NewEmailConfirmation::class, $activationModel);
        $this->assertSame($account->id, $activationModel->account_id);
        $this->assertSame($model->email, $activationModel->newEmail);
        $this->assertNotNull(EmailActivation::findOne($activationModel->key));
    }

    public function testSendNewEmailConfirmation() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'account-with-change-email-init-state');
        /** @var string $key */
        $key = $this->tester->grabFixture('emailActivations', 'currentChangeEmailConfirmation')['key'];
        $model = new SendNewEmailVerificationForm($account, [
            'key' => $key,
            'email' => 'my-new-email@ely.by',
        ]);
        // TODO fix
        // TODO $this->getFunctionMock(EmailValidator::class, 'checkdnsrr')->expects($this->any())->willReturn(true);
        $this->assertTrue($model->performAction());
        $this->assertNull(EmailActivation::findOne($key));
        /** @var EmailActivation $activation */
        $activation = EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]);
        $this->assertInstanceOf(EmailActivation::class, $activation);

        /** @var SendNewEmailConfirmation $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(SendNewEmailConfirmation::class, $job);
        $this->assertSame($account->username, $job->username);
        $this->assertSame('my-new-email@ely.by', $job->email);
        $this->assertSame($activation->key, $job->code);
    }

}
