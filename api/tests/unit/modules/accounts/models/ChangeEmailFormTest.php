<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\ChangeEmailForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\EmailActivation;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\EmailActivationFixture;

class ChangeEmailFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    public function testChangeEmail() {
        /** @var Account $account */
        $account = Account::findOne($this->getAccountId());
        /** @var EmailActivation $newEmailConfirmationFixture */
        $newEmailConfirmationFixture = $this->tester->grabFixture('emailActivations', 'newEmailConfirmation');
        $model = new ChangeEmailForm($account, [
            'key' => $newEmailConfirmationFixture->key,
        ]);
        $this->assertTrue($model->performAction());
        $this->assertNull(EmailActivation::findOne([
            'account_id' => $account->id,
            'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        ]));
        $data = $newEmailConfirmationFixture->data;
        $this->assertSame($data['newEmail'], $account->email);
    }

    private function getAccountId() {
        return $this->tester->grabFixture('accounts', 'account-with-change-email-finish-state')['id'];
    }

}
