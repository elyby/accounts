<?php
namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\SendEmailVerificationForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\confirmations\CurrentEmailConfirmation;
use common\models\EmailActivation;
use common\tasks\SendCurrentEmailConfirmation;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\EmailActivationFixture;

class SendEmailVerificationFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testCreateCode() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new SendEmailVerificationForm($account);
        $activationModel = $model->createCode();
        $this->assertInstanceOf(CurrentEmailConfirmation::class, $activationModel);
        $this->assertEquals($account->id, $activationModel->account_id);
        $this->assertNotNull(EmailActivation::findOne($activationModel->key));
    }

    public function testSendCurrentEmailConfirmation() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new SendEmailVerificationForm($account, [
            'password' => 'password_0',
        ]);
        $this->assertTrue($model->performAction());
        /** @var EmailActivation $activation */
        $activation = EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
        ]);
        $this->assertInstanceOf(EmailActivation::class, $activation);

        /** @var SendCurrentEmailConfirmation $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(SendCurrentEmailConfirmation::class, $job);
        $this->assertSame($account->username, $job->username);
        $this->assertSame($account->email, $job->email);
        $this->assertSame($activation->key, $job->code);
    }

}
