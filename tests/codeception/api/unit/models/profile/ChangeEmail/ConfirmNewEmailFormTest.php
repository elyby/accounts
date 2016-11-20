<?php
namespace codeception\api\unit\models\profile\ChangeEmail;

use api\models\profile\ChangeEmail\ConfirmNewEmailForm;
use common\models\Account;
use common\models\EmailActivation;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;

class ConfirmNewEmailFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testChangeEmail() {
        $accountId = $this->tester->grabFixture('accounts', 'account-with-change-email-finish-state')['id'];
        /** @var Account $account */
        $account = Account::findOne($accountId);
        $newEmailConfirmationFixture = $this->tester->grabFixture('emailActivations', 'newEmailConfirmation');
        $model = new ConfirmNewEmailForm($account, [
            'key' => $newEmailConfirmationFixture['key'],
        ]);
        $this->assertTrue($model->changeEmail());
        $this->assertNull(EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]));
        $data = unserialize($newEmailConfirmationFixture['_data']);
        $this->assertEquals($data['newEmail'], $account->email);
    }

}
