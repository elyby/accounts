<?php
namespace api\tests\unit\modules\internal\models;

use api\modules\accounts\models\BanAccountForm;
use api\modules\internal\helpers\Error as E;
use api\tests\unit\TestCase;
use common\models\Account;
use common\tasks\ClearAccountSessions;

class BanFormTest extends TestCase {

    public function testValidateAccountActivity() {
        $account = new Account();
        $account->status = Account::STATUS_ACTIVE;
        $form = new BanAccountForm($account);
        $form->validateAccountActivity();
        $this->assertEmpty($form->getErrors('account'));

        $account = new Account();
        $account->status = Account::STATUS_BANNED;
        $form = new BanAccountForm($account);
        $form->validateAccountActivity();
        $this->assertSame([E::ACCOUNT_ALREADY_BANNED], $form->getErrors('account'));
    }

    public function testBan() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $model = new BanAccountForm($account);
        $this->assertTrue($model->performAction());
        $this->assertSame(Account::STATUS_BANNED, $account->status);
        /** @var ClearAccountSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearAccountSessions::class, $job);
        $this->assertSame($job->accountId, $account->id);
    }

}
