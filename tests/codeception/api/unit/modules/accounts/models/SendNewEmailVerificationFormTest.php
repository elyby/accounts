<?php
namespace tests\codeception\api\unit\modules\accounts\models;

use api\modules\accounts\models\SendNewEmailVerificationForm;
use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use common\tasks\SendNewEmailConfirmation;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\common\helpers\Mock;
use yii\validators\EmailValidator;

class SendNewEmailVerificationFormTest extends TestCase {

    public function _fixtures() {
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
        $this->assertEquals($account->id, $activationModel->account_id);
        $this->assertEquals($model->email, $activationModel->newEmail);
        $this->assertNotNull(EmailActivation::findOne($activationModel->key));
    }

    public function testSendNewEmailConfirmation() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'account-with-change-email-init-state');
        /** @var SendNewEmailVerificationForm $model */
        $key = $this->tester->grabFixture('emailActivations', 'currentChangeEmailConfirmation')['key'];
        $model = new SendNewEmailVerificationForm($account, [
            'key' => $key,
            'email' => 'my-new-email@ely.by',
        ]);
        Mock::func(EmailValidator::class, 'checkdnsrr')->andReturn(true);
        $this->assertTrue($model->performAction());
        $this->assertNull(EmailActivation::findOne($key));
        /** @var EmailActivation $activation */
        $activation = EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]);
        $this->assertNotNull(EmailActivation::class, $activation);

        /** @var SendNewEmailConfirmation $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(SendNewEmailConfirmation::class, $job);
        $this->assertSame($account->username, $job->username);
        $this->assertSame('my-new-email@ely.by', $job->email);
        $this->assertSame($activation->key, $job->code);
    }

}
