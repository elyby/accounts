<?php
namespace api\tests\unit\modules\internal\models;

use api\modules\accounts\models\PardonAccountForm;
use api\modules\internal\helpers\Error as E;
use api\tests\unit\TestCase;
use common\models\Account;

class PardonFormTest extends TestCase {

    public function testValidateAccountBanned(): void {
        $account = new Account();
        $account->status = Account::STATUS_BANNED;
        $form = new PardonAccountForm($account);
        $form->validateAccountBanned();
        $this->assertEmpty($form->getErrors('account'));

        $account = new Account();
        $account->status = Account::STATUS_ACTIVE;
        $form = new PardonAccountForm($account);
        $form->validateAccountBanned();
        $this->assertSame([E::ACCOUNT_NOT_BANNED], $form->getErrors('account'));
    }

    public function testPardon(): void {
        /** @var Account|\PHPUnit\Framework\MockObject\MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->onlyMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $account->status = Account::STATUS_BANNED;
        $model = new PardonAccountForm($account);
        $this->assertTrue($model->performAction());
        $this->assertSame(Account::STATUS_ACTIVE, $account->status);
    }

}
