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
        /** @var Account $account */
        $account = Account::findOne($this->getAccountId());
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
        $this->tester->canSeeAmqpMessageIsCreated('events');
    }

    public function testCreateTask() {
        /** @var Account $account */
        $account = Account::findOne($this->getAccountId());
        $model = new ConfirmNewEmailForm($account);
        $model->createTask(1, 'test1@ely.by', 'test@ely.by');
        $message = $this->tester->grabLastSentAmqpMessage('events');
        $body = json_decode($message->getBody(), true);
        $this->assertEquals(1, $body['accountId']);
        $this->assertEquals('test1@ely.by', $body['newEmail']);
        $this->assertEquals('test@ely.by', $body['oldEmail']);
    }

    private function getAccountId() {
        return $this->tester->grabFixture('accounts', 'account-with-change-email-finish-state')['id'];
    }

}
