<?php
namespace tests\codeception\api\unit\modules\accounts\models;

use api\modules\accounts\models\ChangeEmailForm;
use common\models\Account;
use common\models\EmailActivation;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;

class ChangeEmailFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testChangeEmail() {
        /** @var Account $account */
        $account = Account::findOne($this->getAccountId());
        $newEmailConfirmationFixture = $this->tester->grabFixture('emailActivations', 'newEmailConfirmation');
        $model = new ChangeEmailForm($account, [
            'key' => $newEmailConfirmationFixture['key'],
        ]);
        $this->assertTrue($model->performAction());
        $this->assertNull(EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]));
        /** @noinspection UnserializeExploitsInspection */
        $data = unserialize($newEmailConfirmationFixture['_data']);
        $this->assertEquals($data['newEmail'], $account->email);
    }

    private function getAccountId() {
        return $this->tester->grabFixture('accounts', 'account-with-change-email-finish-state')['id'];
    }

}
