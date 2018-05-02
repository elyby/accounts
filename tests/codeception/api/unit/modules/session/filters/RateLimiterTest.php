<?php
namespace tests\codeception\api\unit\modules\session\filters;

use api\modules\session\filters\RateLimiter;
use common\models\OauthClient;
use Faker\Provider\Internet;
use tests\codeception\api\unit\TestCase;
use Yii;
use yii\redis\Connection;
use yii\web\Request;

class RateLimiterTest extends TestCase {

    public function testCheckRateLimiterWithOldAuthserver() {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->getMockBuilder(Connection::class)
            ->setMethods(['executeCommand'])
            ->getMock();

        $redis->expects($this->never())
            ->method('executeCommand');

        Yii::$app->set('redis', $redis);

        /** @var RateLimiter|\PHPUnit_Framework_MockObject_MockObject $filter */
        $filter = $this->getMockBuilder(RateLimiter::class)
            ->setConstructorArgs([[
                'authserverDomain' => Yii::$app->params['authserverHost'],
            ]])
            ->setMethods(['getServer'])
            ->getMock();

        $filter->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue(new OauthClient()));

        $filter->checkRateLimit(null, new Request(), null, null);
    }

    public function testCheckRateLimiterWithValidServerId() {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->getMockBuilder(Connection::class)
            ->setMethods(['executeCommand'])
            ->getMock();

        $redis->expects($this->never())
            ->method('executeCommand');

        Yii::$app->set('redis', $redis);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getHostInfo'])
            ->getMock();

        $request->expects($this->any())
            ->method('getHostInfo')
            ->will($this->returnValue('http://authserver.ely.by'));

        $filter = new RateLimiter([
            'authserverDomain' => Yii::$app->params['authserverHost'],
        ]);
        $filter->checkRateLimit(null, $request, null, null);
    }

    /**
     * @expectedException \yii\web\TooManyRequestsHttpException
     */
    public function testCheckRateLimiter() {
        /** @var Connection|\PHPUnit_Framework_MockObject_MockObject $redis */
        $redis = $this->getMockBuilder(Connection::class)
            ->setMethods(['executeCommand'])
            ->getMock();

        $redis->expects($this->exactly(5))
            ->method('executeCommand')
            ->will($this->onConsecutiveCalls('1', '1', '2', '3', '4'));

        Yii::$app->set('redis', $redis);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getUserIP'])
            ->getMock();

        $request->expects($this->any())
            ->method('getUserIp')
            ->will($this->returnValue(Internet::localIpv4()));

        /** @var RateLimiter|\PHPUnit_Framework_MockObject_MockObject $filter */
        $filter = $this->getMockBuilder(RateLimiter::class)
            ->setConstructorArgs([[
                'limit' => 3,
                'authserverDomain' => Yii::$app->params['authserverHost'],
            ]])
            ->setMethods(['getServer'])
            ->getMock();

        $filter->expects($this->any())
            ->method('getServer')
            ->will($this->returnValue(null));

        for ($i = 0; $i < 5; $i++) {
            $filter->checkRateLimit(null, $request, null, null);
        }
    }

}
