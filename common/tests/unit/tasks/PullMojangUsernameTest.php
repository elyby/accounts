<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\components\Mojang\Api;
use common\components\Mojang\exceptions\NoContentException;
use common\components\Mojang\response\UsernameToUUIDResponse;
use common\models\Account;
use common\models\MojangUsername;
use common\tasks\PullMojangUsername;
use common\tests\fixtures\MojangUsernameFixture;
use common\tests\unit\TestCase;
use yii\queue\Queue;

/**
 * @covers \common\tasks\PullMojangUsername
 */
class PullMojangUsernameTest extends TestCase {

    private $expectedResponse;

    /**
     * @var PullMojangUsername
     */
    private $task;

    public function _fixtures() {
        return [
            'mojangUsernames' => MojangUsernameFixture::class,
        ];
    }

    public function _before() {
        parent::_before();

        /** @var PullMojangUsername|\PHPUnit_Framework_MockObject_MockObject $task */
        $task = $this->getMockBuilder(PullMojangUsername::class)
            ->setMethods(['createMojangApi'])
            ->getMock();

        /** @var Api|\PHPUnit_Framework_MockObject_MockObject $apiMock */
        $apiMock = $this->getMockBuilder(Api::class)
            ->setMethods(['usernameToUUID'])
            ->getMock();

        $apiMock
            ->expects($this->any())
            ->method('usernameToUUID')
            ->willReturnCallback(function() {
                if ($this->expectedResponse === false) {
                    throw new NoContentException();
                }

                return $this->expectedResponse;
            });

        $task
            ->expects($this->any())
            ->method('createMojangApi')
            ->willReturn($apiMock);

        $this->task = $task;
    }

    public function testCreateFromAccount() {
        $account = new Account();
        $account->username = 'find-me';
        $result = PullMojangUsername::createFromAccount($account);
        $this->assertSame('find-me', $result->username);
    }

    public function testExecuteUsernameExists() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = '069a79f444e94726a5befca90e38aaf5';
        $expectedResponse->name = 'Notch';
        $this->expectedResponse = $expectedResponse;

        /** @var \common\models\MojangUsername $mojangUsernameFixture */
        $mojangUsernameFixture = $this->tester->grabFixture('mojangUsernames', 'Notch');
        $this->task->username = 'Notch';
        $this->task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Notch');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertGreaterThan($mojangUsernameFixture->last_pulled_at, $mojangUsername->last_pulled_at);
        $this->assertLessThanOrEqual(time(), $mojangUsername->last_pulled_at);
    }

    public function testExecuteChangedUsernameExists() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = '069a79f444e94726a5befca90e38aaf5';
        $expectedResponse->name = 'Notch';
        $this->expectedResponse = $expectedResponse;

        /** @var MojangUsername $mojangUsernameFixture */
        $mojangUsernameFixture = $this->tester->grabFixture('mojangUsernames', 'Notch');
        $this->task->username = 'Notch';
        $this->task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Notch');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertGreaterThan($mojangUsernameFixture->last_pulled_at, $mojangUsername->last_pulled_at);
        $this->assertLessThanOrEqual(time(), $mojangUsername->last_pulled_at);
    }

    public function testExecuteChangedUsernameNotExists() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = '607153852b8c4909811f507ed8ee737f';
        $expectedResponse->name = 'Chest';
        $this->expectedResponse = $expectedResponse;

        $this->task->username = 'Chest';
        $this->task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Chest');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
    }

    public function testExecuteRemoveIfExistsNoMore() {
        $this->expectedResponse = false;

        $username = $this->tester->grabFixture('mojangUsernames', 'not-exists')['username'];
        $this->task->username = $username;
        $this->task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($username);
        $this->assertNull($mojangUsername);
    }

    public function testExecuteUuidUpdated() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = 'f498513ce8c84773be26ecfc7ed5185d';
        $expectedResponse->name = 'jeb';
        $this->expectedResponse = $expectedResponse;

        /** @var MojangUsername $mojangInfo */
        $mojangInfo = $this->tester->grabFixture('mojangUsernames', 'uuid-changed');
        $username = $mojangInfo['username'];
        $this->task->username = $username;
        $this->task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($username);
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertNotEquals($mojangInfo->uuid, $mojangUsername->uuid);
    }

}
