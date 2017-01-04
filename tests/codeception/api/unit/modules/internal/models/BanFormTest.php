<?php
namespace tests\codeception\api\unit\modules\internal\models;

use api\modules\internal\helpers\Error as E;
use api\modules\internal\models\BanForm;
use common\models\Account;
use tests\codeception\api\unit\TestCase;

class BanFormTest extends TestCase {

    public function testValidateAccountActivity() {
        $account = new Account();
        $account->status = Account::STATUS_ACTIVE;
        $form = new BanForm($account);
        $form->validateAccountActivity();
        $this->assertEmpty($form->getErrors('account'));

        $account = new Account();
        $account->status = Account::STATUS_BANNED;
        $form = new BanForm($account);
        $form->validateAccountActivity();
        $this->assertEquals([E::ACCOUNT_ALREADY_BANNED], $form->getErrors('account'));
    }

    public function testBan() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $model = new BanForm($account);
        $this->assertTrue($model->ban());
        $this->assertEquals(Account::STATUS_BANNED, $account->status);
        $this->tester->canSeeAmqpMessageIsCreated('events');
    }

    public function testCreateTask() {
        $account = new Account();
        $account->id = 3;

        $model = new BanForm($account);
        $model->createTask();
        $message = json_decode($this->tester->grabLastSentAmqpMessage('events')->body, true);
        $this->assertSame(3, $message['accountId']);
        $this->assertSame(-1, $message['duration']);
        $this->assertSame('', $message['message']);

        $model = new BanForm($account);
        $model->duration = 123;
        $model->message = 'test';
        $model->createTask();
        $message = json_decode($this->tester->grabLastSentAmqpMessage('events')->body, true);
        $this->assertSame(3, $message['accountId']);
        $this->assertSame(123, $message['duration']);
        $this->assertSame('test', $message['message']);
    }

}
