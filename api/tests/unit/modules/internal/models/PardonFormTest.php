<?php
namespace api\tests\unit\modules\internal\models;

use api\modules\accounts\models\PardonAccountForm;
use api\modules\internal\helpers\Error as E;
use api\tests\unit\TestCase;
use common\models\Account;

class PardonFormTest extends TestCase {

    public function testValidateAccountBanned() {
        $account = new Account();
        $account->status = Account::STATUS_BANNED;
        $form = new PardonAccountForm($account);
        $form->validateAccountBanned();
        $this->assertEmpty($form->getErrors('account'));

        $account = new Account();
        $account->status = Account::STATUS_ACTIVE;
        $form = new PardonAccountForm($account);
        $form->validateAccountBanned();
        $this->assertEquals([E::ACCOUNT_NOT_BANNED], $form->getErrors('account'));
    }

    public function testPardon() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $account->status = Account::STATUS_BANNED;
        $model = new PardonAccountForm($account);
        $this->assertTrue($model->performAction());
        $this->assertEquals(Account::STATUS_ACTIVE, $account->status);
    }

}
