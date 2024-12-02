<?php
declare(strict_types=1);

namespace api\tests\unit\modules\internal\models;

use api\modules\accounts\models\BanAccountForm;
use api\modules\internal\helpers\Error as E;
use api\tests\unit\TestCase;
use common\models\Account;
use common\tasks\ClearAccountSessions;
use ReflectionObject;

class BanAccountFormTest extends TestCase {

    public function testValidateAccountActivity(): void {
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

    public function testBan(): void {
        /** @var Account|\PHPUnit\Framework\MockObject\MockObject $account */
        $account = $this->createPartialMock(Account::class, ['save']);
        $account->expects($this->once())->method('save')->willReturn(true);
        $account->id = 123;

        $model = new BanAccountForm($account);
        $this->assertTrue($model->performAction());
        $this->assertSame(Account::STATUS_BANNED, $account->status);
        /** @var ClearAccountSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearAccountSessions::class, $job);
        $obj = new ReflectionObject($job);
        $property = $obj->getProperty('accountId');
        $property->setAccessible(true);
        $this->assertSame(123, $property->getValue($job));
    }

}
