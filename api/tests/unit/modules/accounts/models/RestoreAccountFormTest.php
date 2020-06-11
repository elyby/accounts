<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\RestoreAccountForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\tasks\CreateWebHooksDeliveries;
use common\tests\fixtures\AccountFixture;
use Yii;
use yii\queue\Queue;

class RestoreAccountFormTest extends TestCase {

    /**
     * @var Queue|\PHPUnit\Framework\MockObject\MockObject
     */
    private Queue $queue;

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function _before(): void {
        parent::_before();

        $this->queue = $this->createMock(Queue::class);
        Yii::$app->set('queue', $this->queue);
    }

    public function testPerformAction() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'deleted-account');
        $this->queue
            ->expects($this->once())
            ->method('push')
            ->withConsecutive(
                [$this->callback(function(CreateWebHooksDeliveries $task) use ($account): bool {
                    $this->assertSame($account->id, $task->payloads['id']);
                    return true;
                })],
            );

        $model = new RestoreAccountForm($account);
        $this->assertTrue($model->performAction());
        $this->assertSame(Account::STATUS_ACTIVE, $account->status);
        $this->assertNull($account->deleted_at);
    }

    public function testPerformActionForNotDeletedAccount() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new RestoreAccountForm($account);
        $this->assertFalse($model->performAction());
        $this->assertSame(['account' => ['error.account_not_deleted']], $model->getErrors());
    }

}
