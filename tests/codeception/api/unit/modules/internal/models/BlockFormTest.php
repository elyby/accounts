<?php
namespace tests\codeception\api\unit\modules\internal\models;

use api\modules\internal\models\BlockForm;
use common\models\Account;
use tests\codeception\api\unit\TestCase;

class BlockFormTest extends TestCase {

    public function testBan() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $model = new BlockForm($account);
        $this->assertTrue($model->ban());
        $this->assertEquals(Account::STATUS_BANNED, $account->status);
        $this->tester->canSeeAmqpMessageIsCreated('events');
    }

    public function testCreateTask() {
        $account = new Account();
        $account->id = 3;

        $model = new BlockForm($account);
        $model->createTask();
        $message = json_decode($this->tester->grabLastSentAmqpMessage('events')->body, true);
        $this->assertSame(3, $message['accountId']);
        $this->assertSame(-1, $message['duration']);
        $this->assertSame('', $message['message']);

        $model = new BlockForm($account);
        $model->duration = 123;
        $model->message = 'test';
        $model->createTask();
        $message = json_decode($this->tester->grabLastSentAmqpMessage('events')->body, true);
        $this->assertSame(3, $message['accountId']);
        $this->assertSame(123, $message['duration']);
        $this->assertSame('test', $message['message']);
    }

}
