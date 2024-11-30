<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\DeleteAccountForm;
use api\tests\unit\TestCase;
use Codeception\Util\ReflectionHelper;
use common\models\Account;
use common\notifications\AccountEditNotification;
use common\tasks\CreateWebHooksDeliveries;
use common\tasks\DeleteAccount;
use common\tests\fixtures\AccountFixture;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionObject;
use Yii;
use yii\queue\Queue;

class DeleteAccountFormTest extends TestCase {

    /**
     * @var Queue|MockObject
     */
    private Queue|MockObject $queue;

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
        $account = $this->tester->grabFixture('accounts', 'admin');
        $this->queue
            ->expects($this->once())
            ->method('delay')
            ->with($this->equalToWithDelta(60 * 60 * 24 * 7, 5))
            ->willReturnSelf();
        $this->queue
            ->expects($this->exactly(2))
            ->method('push')
            ->willReturnCallback(function($task) use ($account): bool {
                if ($task instanceof CreateWebHooksDeliveries) {
                    /** @var AccountEditNotification $notification */
                    $notification = ReflectionHelper::readPrivateProperty($task, 'notification');
                    $this->assertInstanceOf(AccountEditNotification::class, $notification);
                    $this->assertSame($account->id, $notification->getPayloads()['id']);
                    $this->assertTrue($notification->getPayloads()['isDeleted']);

                    return true;
                }

                if ($task instanceof DeleteAccount) {
                    $obj = new ReflectionObject($task);
                    $property = $obj->getProperty('accountId');
                    $this->assertSame($account->id, $property->getValue($task));

                    return true;
                }

                return false;
            });

        $model = new DeleteAccountForm($account, [
            'password' => 'password_0',
        ]);
        $this->assertTrue($model->performAction());
        $this->assertSame(Account::STATUS_DELETED, $account->status);
        $this->assertEqualsWithDelta(time(), $account->deleted_at, 5);
    }

    public function testPerformActionWithInvalidPassword() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $model = new DeleteAccountForm($account, [
            'password' => 'invalid password',
        ]);
        $this->assertFalse($model->performAction());
        $this->assertSame(['password' => ['error.password_incorrect']], $model->getErrors());
    }

    public function testPerformActionForAlreadyDeletedAccount() {
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'deleted-account');
        $model = new DeleteAccountForm($account, [
            'password' => 'password_0',
        ]);
        $this->assertFalse($model->performAction());
        $this->assertSame(['account' => ['error.account_already_deleted']], $model->getErrors());
    }

}
