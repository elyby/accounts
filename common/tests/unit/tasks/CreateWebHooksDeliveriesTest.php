<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\notifications\NotificationInterface;
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
        ];
    }

    public function testExecute() {
        $notification = new class implements NotificationInterface {
            public static function getType(): string {
                return 'account.edit';
            }

            public function getPayloads(): array {
                return ['key' => 'value'];
            }
        };

        $queue = $this->createMock(Queue::class);
        $queue->expects($this->exactly(2))->method('push')->withConsecutive(
            [$this->callback(function(DeliveryWebHook $task): bool {
                $this->assertSame('account.edit', $task->type);
                $this->assertSame(['key' => 'value'], $task->payloads);
                $this->assertSame('http://localhost:80/webhooks/ely', $task->url);
                $this->assertSame('my-secret', $task->secret);

                return true;
            })],
            [$this->callback(function(DeliveryWebHook $task): bool {
                $this->assertSame('account.edit', $task->type);
                $this->assertSame(['key' => 'value'], $task->payloads);
                $this->assertSame('http://localhost:81/webhooks/ely', $task->url);
                $this->assertNull($task->secret);

                return true;
            })],
        );

        $task = new CreateWebHooksDeliveries($notification);
        $task->execute($queue);
    }

}
