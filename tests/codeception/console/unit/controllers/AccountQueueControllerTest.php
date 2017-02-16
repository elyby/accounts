<?php
namespace codeception\console\unit\controllers;

use common\components\Mojang\Api;
use common\components\Mojang\exceptions\NoContentException;
use common\components\Mojang\response\UsernameToUUIDResponse;
use common\models\amqp\AccountBanned;
use common\models\amqp\UsernameChanged;
use common\models\MojangUsername;
use console\controllers\AccountQueueController;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\MojangUsernameFixture;
use tests\codeception\console\unit\TestCase;
use Yii;

class AccountQueueControllerTest extends TestCase {

    /**
     * @var AccountQueueController
     */
    private $controller;

    private $expectedResponse;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'mojangUsernames' => MojangUsernameFixture::class,
        ];
    }

    public function _before() {
        parent::_before();

        /** @var AccountQueueController|\PHPUnit_Framework_MockObject_MockObject $controller */
        $controller = $this->getMockBuilder(AccountQueueController::class)
            ->setMethods(['createMojangApi'])
            ->setConstructorArgs(['account-queue', Yii::$app])
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
                } else {
                    return $this->expectedResponse;
                }
            });

        $controller
            ->expects($this->any())
            ->method('createMojangApi')
            ->willReturn($apiMock);

        $this->controller = $controller;
    }

    public function testRouteUsernameChangedUsernameExists() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = '069a79f444e94726a5befca90e38aaf5';
        $expectedResponse->name = 'Notch';
        $this->expectedResponse = $expectedResponse;

        /** @var \common\models\Account $accountInfo */
        $accountInfo = $this->tester->grabFixture('accounts', 'admin');
        /** @var MojangUsername $mojangUsernameFixture */
        $mojangUsernameFixture = $this->tester->grabFixture('mojangUsernames', 'Notch');
        $body = new UsernameChanged([
            'accountId' => $accountInfo->id,
            'oldUsername' => $accountInfo->username,
            'newUsername' => 'Notch',
        ]);
        $this->controller->routeUsernameChanged($body);
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Notch');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertGreaterThan($mojangUsernameFixture->last_pulled_at, $mojangUsername->last_pulled_at);
        $this->assertLessThanOrEqual(time(), $mojangUsername->last_pulled_at);
    }

    public function testRouteUsernameChangedUsernameNotExists() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = '607153852b8c4909811f507ed8ee737f';
        $expectedResponse->name = 'Chest';
        $this->expectedResponse = $expectedResponse;

        /** @var \common\models\Account $accountInfo */
        $accountInfo = $this->tester->grabFixture('accounts', 'admin');
        $body = new UsernameChanged([
            'accountId' => $accountInfo['id'],
            'oldUsername' => $accountInfo['username'],
            'newUsername' => 'Chest',
        ]);
        $this->controller->routeUsernameChanged($body);
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne('Chest');
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
    }

    public function testRouteUsernameChangedRemoveIfExistsNoMore() {
        $this->expectedResponse = false;

        /** @var \common\models\Account $accountInfo */
        $accountInfo = $this->tester->grabFixture('accounts', 'admin');
        $username = $this->tester->grabFixture('mojangUsernames', 'not-exists')['username'];
        $body = new UsernameChanged([
            'accountId' => $accountInfo['id'],
            'oldUsername' => $accountInfo['username'],
            'newUsername' => $username,
        ]);
        $this->controller->routeUsernameChanged($body);
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($username);
        $this->assertNull($mojangUsername);
    }

    public function testRouteUsernameChangedUuidUpdated() {
        $expectedResponse = new UsernameToUUIDResponse();
        $expectedResponse->id = 'f498513ce8c84773be26ecfc7ed5185d';
        $expectedResponse->name = 'jeb';
        $this->expectedResponse = $expectedResponse;

        /** @var \common\models\Account $accountInfo */
        $accountInfo = $this->tester->grabFixture('accounts', 'admin');
        /** @var MojangUsername $mojangInfo */
        $mojangInfo = $this->tester->grabFixture('mojangUsernames', 'uuid-changed');
        $username = $mojangInfo['username'];
        $body = new UsernameChanged([
            'accountId' => $accountInfo['id'],
            'oldUsername' => $accountInfo['username'],
            'newUsername' => $username,
        ]);
        $this->controller->routeUsernameChanged($body);
        /** @var MojangUsername|null $mojangUsername */
        $mojangUsername = MojangUsername::findOne($username);
        $this->assertInstanceOf(MojangUsername::class, $mojangUsername);
        $this->assertNotEquals($mojangInfo->uuid, $mojangUsername->uuid);
    }

    public function testRouteAccountBanned() {
        /** @var \common\models\Account $bannedAccount */
        $bannedAccount = $this->tester->grabFixture('accounts', 'banned-account');
        $this->tester->haveFixtures([
            'oauthSessions' => \tests\codeception\common\fixtures\OauthSessionFixture::class,
            'minecraftAccessKeys' => \tests\codeception\common\fixtures\MinecraftAccessKeyFixture::class,
            'authSessions' => \tests\codeception\common\fixtures\AccountSessionFixture::class,
        ]);

        $body = new AccountBanned();
        $body->accountId = $bannedAccount->id;

        $this->controller->routeAccountBanned($body);
        $this->assertEmpty($bannedAccount->sessions);
        $this->assertEmpty($bannedAccount->minecraftAccessKeys);
        $this->assertEmpty($bannedAccount->oauthSessions);
    }

}
