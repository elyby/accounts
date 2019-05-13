<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\models\Account;
use common\models\MojangUsername;
use common\tasks\PullMojangUsername;
use common\tests\fixtures\MojangUsernameFixture;
use common\tests\unit\TestCase;
use Ely\Mojang\Api as MojangApi;
use Ely\Mojang\Exception\NoContentException;
use Ely\Mojang\Response\ProfileInfo;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Yii;
use yii\queue\Queue;

/**
 * @covers \common\tasks\PullMojangUsername
 */
class PullMojangUsernameTest extends TestCase {

    /** @var \PHPUnit\Framework\MockObject\Builder\InvocationMocker */
    private $mockedMethod;

    public function _fixtures() {
        return [
            'mojangUsernames' => MojangUsernameFixture::class,
        ];
    }

    public function _before() {
        parent::_before();

        /** @var \PHPUnit\Framework\MockObject\MockObject|MojangApi $mockApi */
        $mockApi = $this->createMock(MojangApi::class);
        $this->mockedMethod = $mockApi->method('usernameToUUID');

        Yii::$container->set(MojangApi::class, $mockApi);
    }

    public function testCreateFromAccount() {
        $account = new Account();
        $account->username = 'find-me';
        $result = PullMojangUsername::createFromAccount($account);
        $this->assertSame('find-me', $result->username);
    }

    public function testExecuteUsernameExists() {
        $this->mockedMethod->willReturn(new ProfileInfo('069a79f444e94726a5befca90e38aaf5', 'Notch'));

        /** @var \common\models\MojangUsername $mojangUsernameFixture */
        $mojangUsernameFixture = $this->tester->grabFixture('mojangUsernames', 'Notch');
        $task = new PullMojangUsername();
        $task->username = 'Notch';
        $task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Notch');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertGreaterThan($mojangUsernameFixture->last_pulled_at, $mojangUsername->last_pulled_at);
        $this->assertLessThanOrEqual(time(), $mojangUsername->last_pulled_at);
    }

    public function testExecuteChangedUsernameExists() {
        $this->mockedMethod->willReturn(new ProfileInfo('069a79f444e94726a5befca90e38aaf5', 'Notch'));

        /** @var MojangUsername $mojangUsernameFixture */
        $mojangUsernameFixture = $this->tester->grabFixture('mojangUsernames', 'Notch');
        $task = new PullMojangUsername();
        $task->username = 'Notch';
        $task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Notch');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertGreaterThan($mojangUsernameFixture->last_pulled_at, $mojangUsername->last_pulled_at);
        $this->assertLessThanOrEqual(time(), $mojangUsername->last_pulled_at);
    }

    public function testExecuteChangedUsernameNotExists() {
        $this->mockedMethod->willReturn(new ProfileInfo('607153852b8c4909811f507ed8ee737f', 'Chest'));

        $task = new PullMojangUsername();
        $task->username = 'Chest';
        $task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Chest');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
    }

    public function testExecuteRemoveIfExistsNoMore() {
        $this->mockedMethod->willThrowException(new NoContentException(new Request('', ''), new Response()));

        $username = $this->tester->grabFixture('mojangUsernames', 'not-exists')['username'];
        $task = new PullMojangUsername();
        $task->username = $username;
        $task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($username);
        $this->assertNull($mojangUsername);
    }

    public function testExecuteUuidUpdated() {
        $this->mockedMethod->willReturn(new ProfileInfo('f498513ce8c84773be26ecfc7ed5185d', 'jeb'));

        /** @var MojangUsername $mojangInfo */
        $mojangInfo = $this->tester->grabFixture('mojangUsernames', 'uuid-changed');
        $username = $mojangInfo['username'];
        $task = new PullMojangUsername();
        $task->username = $username;
        $task->execute(mock(Queue::class));
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($username);
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertNotSame($mojangInfo->uuid, $mojangUsername->uuid);
    }

}
