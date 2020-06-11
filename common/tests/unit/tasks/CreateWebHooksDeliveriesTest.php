<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\models\Account;
use common\tasks\CreateWebHooksDeliveries;
use common\tasks\DeliveryWebHook;
use common\tests\fixtures;
use common\tests\unit\TestCase;
use yii\queue\Queue;

/**
 * @covers \common\tasks\CreateWebHooksDeliveries
 */
class CreateWebHooksDeliveriesTest extends TestCase {

    public function _fixtures(): array {
        return [
            'webhooks' => fixtures\WebHooksFixture::class,
            'webhooksEvents' => fixtures\WebHooksEventsFixture::class,
        ];
    }

    public function testCreateAccountEdit() {
        $account = new Account();
        $account->id = 123;
        $account->username = 'mock-username';
        $account->uuid = 'afc8dc7a-4bbf-4d3a-8699-68890088cf84';
        $account->email = 'mock@ely.by';
        $account->lang = 'en';
        $account->status = Account::STATUS_ACTIVE;
        $account->created_at = 1531008814;
        $changedAttributes = [
            'username' => 'old-username',
            'uuid' => 'e05d33e9-ff91-4d26-9f5c-8250f802a87a',
            'email' => 'old-email@ely.by',
            'status' => 0,
        ];
        $result = CreateWebHooksDeliveries::createAccountEdit($account, $changedAttributes);
        $this->assertSame('account.edit', $result->type);
        $this->assertEmpty(array_diff_assoc([
            'id' => 123,
            'uuid' => 'afc8dc7a-4bbf-4d3a-8699-68890088cf84',
            'username' => 'mock-username',
            'email' => 'mock@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'registered' => '2018-07-08T00:13:34+00:00',
        ], $result->payloads));
        $this->assertSame($changedAttributes, $result->payloads['changedAttributes']);
    }

    public function testExecute() {
        $task = new CreateWebHooksDeliveries('account.edit', [
            'id' => 123,
            'uuid' => 'afc8dc7a-4bbf-4d3a-8699-68890088cf84',
            'username' => 'mock-username',
            'email' => 'mock@ely.by',
            'lang' => 'en',
            'isActive' => true,
            'registered' => '2018-07-08T00:13:34+00:00',
            'changedAttributes' => [
                'username' => 'old-username',
                'uuid' => 'e05d33e9-ff91-4d26-9f5c-8250f802a87a',
                'email' => 'old-email@ely.by',
                'status' => 0,
            ],
        ]);
        $task->execute($this->createMock(Queue::class));
        /** @var DeliveryWebHook[] $tasks */
        $tasks = $this->tester->grabQueueJobs();
        $this->assertCount(2, $tasks);

        $this->assertInstanceOf(DeliveryWebHook::class, $tasks[0]);
        $this->assertSame($task->type, $tasks[0]->type);
        $this->assertSame($task->payloads, $tasks[0]->payloads);
        $this->assertSame('http://localhost:80/webhooks/ely', $tasks[0]->url);
        $this->assertSame('my-secret', $tasks[0]->secret);

        $this->assertInstanceOf(DeliveryWebHook::class, $tasks[1]);
        $this->assertSame($task->type, $tasks[1]->type);
        $this->assertSame($task->payloads, $tasks[1]->payloads);
        $this->assertSame('http://localhost:81/webhooks/ely', $tasks[1]->url);
        $this->assertNull($tasks[1]->secret);
    }

}
