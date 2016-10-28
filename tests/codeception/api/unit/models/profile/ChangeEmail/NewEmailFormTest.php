<?php
namespace codeception\api\unit\models\profile\ChangeEmail;

use api\models\profile\ChangeEmail\NewEmailForm;
use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\models\EmailActivation;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;

class NewEmailFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testCreateCode() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new NewEmailForm($account);
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
        /** @var NewEmailForm $model */
        $key = $this->tester->grabFixture('emailActivations', 'currentChangeEmailConfirmation')['key'];
        $model = new NewEmailForm($account, [
            'key' => $key,
            'email' => 'my-new-email@ely.by',
        ]);
        $this->assertTrue($model->sendNewEmailConfirmation());
        $this->assertNull(EmailActivation::findOne($key));
        $this->assertNotNull(EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]));
        $this->tester->canSeeEmailIsSent();
    }

}
