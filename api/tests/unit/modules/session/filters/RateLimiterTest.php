<?php
declare(strict_types=1);

namespace api\tests\unit\modules\session\filters;

use api\modules\session\filters\RateLimiter;
use api\tests\unit\TestCase;
use common\models\OauthClient;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;
use yii\base\Action;
use yii\filters\RateLimitInterface;
use yii\redis\Connection;
use yii\web\Request;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

class RateLimiterTest extends TestCase {

    private RateLimitInterface&MockObject $user;

    private Response&MockObject $response;

    private Action&MockObject $action;

    public function testCheckRateLimiterWithOldAuthserver(): void {
        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $redis */
        $redis = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['executeCommand'])
            ->getMock();

        $redis->expects($this->never())
            ->method('executeCommand');

        Yii::$app->set('redis', $redis);

        /** @var RateLimiter|\PHPUnit\Framework\MockObject\MockObject $filter */
        $filter = $this->getMockBuilder(RateLimiter::class)
            ->setConstructorArgs([[
                'authserverDomain' => 'authserver.ely.by',
            ]])
            ->onlyMethods(['getServer'])
            ->getMock();

        $filter->method('getServer')
            ->willReturn(new OauthClient());

        $filter->checkRateLimit($this->user, new Request(), $this->response, $this->action);
    }

    public function testCheckRateLimiterWithValidServerId(): void {
        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $redis */
        $redis = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['executeCommand'])
            ->getMock();

        $redis->expects($this->never())
            ->method('executeCommand');

        Yii::$app->set('redis', $redis);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getHostInfo'])
            ->getMock();

        $request->method('getHostInfo')
            ->willReturn('http://authserver.ely.by');

        $filter = new RateLimiter([
            'authserverDomain' => 'authserver.ely.by',
        ]);
        $filter->checkRateLimit($this->user, $request, $this->response, $this->action);
    }

    public function testCheckRateLimiter(): void {
        $this->expectException(TooManyRequestsHttpException::class);

        /** @var Connection|\PHPUnit\Framework\MockObject\MockObject $redis */
        $redis = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['executeCommand'])
            ->getMock();

        $redis->expects($this->exactly(5))
            ->method('executeCommand')
            ->will($this->onConsecutiveCalls('1', '1', '2', '3', '4'));

        Yii::$app->set('redis', $redis);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getUserIP'])
            ->getMock();

        $request->method('getUserIp')
            ->willReturn('10.1.1.17');

        /** @var RateLimiter|\PHPUnit\Framework\MockObject\MockObject $filter */
        $filter = $this->getMockBuilder(RateLimiter::class)
            ->setConstructorArgs([[
                'limit' => 3,
                'authserverDomain' => 'authserver.ely.by',
            ]])
            ->onlyMethods(['getServer'])
            ->getMock();

        $filter->method('getServer')
            ->willReturn(null);

        for ($i = 0; $i < 5; $i++) {
            $filter->checkRateLimit($this->user, $request, $this->response, $this->action);
        }
    }

    protected function _setUp(): void {
        parent::_setUp();
        $this->user = $this->createMock(RateLimitInterface::class);
        $this->response = $this->createMock(Response::class);
        $this->action = $this->createMock(Action::class);
    }

}
