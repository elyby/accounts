<?php
declare(strict_types=1);

namespace api\tests\unit\filters;

use api\filters\IpRateLimiter;
use api\tests\unit\TestCase;
use Yii;
use yii\base\Action;
use yii\base\Controller;
use yii\redis\Connection;
use yii\web\Request;
use yii\web\TooManyRequestsHttpException;

final class IpRateLimiterTest extends TestCase {

    /**
     * @var array<string, int> key => counter
     */
    private array $counters = [];

    /**
     * @var array<string, int> key => TTL
     */
    private array $ttls = [];

    protected function setUp(): void {
        parent::setUp();
        // Emulates the Lua script: INCR + EXPIRE on the first increment
        $redis = $this->getMockBuilder(Connection::class)->onlyMethods(['executeCommand'])->getMock();
        $redis->method('executeCommand')->willReturnCallback(function(string $command, array $params): int {
            $this->assertSame('EVAL', $command);
            /** @var array{string, int, string, int} $params */
            [, , $key, $ttl] = $params;
            $this->counters[$key] = ($this->counters[$key] ?? 0) + 1;
            if ($this->counters[$key] === 1) {
                $this->ttls[$key] = $ttl;
            }

            return $this->counters[$key];
        });
        Yii::$app->set('redis', $redis);
    }

    public function testLimitIsExceeded(): void {
        $filter = $this->createFilter([60 => 2]);
        $this->assertTrue($this->request($filter, '10.0.0.1'));
        $this->assertTrue($this->request($filter, '10.0.0.1'));

        $this->expectException(TooManyRequestsHttpException::class);
        $this->request($filter, '10.0.0.1');
    }

    public function testIpsAreLimitedSeparately(): void {
        $filter = $this->createFilter([60 => 1]);
        $this->assertTrue($this->request($filter, '10.0.0.1'));
        $this->assertTrue($this->request($filter, '10.0.0.2'));
    }

    public function testIpv6IsLimitedPerSubnet(): void {
        $filter = $this->createFilter([60 => 1]);
        $this->assertTrue($this->request($filter, '2001:db8:1:2::1'));

        $this->expectException(TooManyRequestsHttpException::class);
        $this->request($filter, '2001:db8:1:2:ffff:ffff:ffff:ffff');
    }

    public function testEveryWindowHasOwnCounterAndTtl(): void {
        $filter = $this->createFilter([60 => 5, 3600 => 1]);
        $this->assertTrue($this->request($filter, '10.0.0.1'));
        $this->assertSame([
            'test:60:10.0.0.1' => 60,
            'test:3600:10.0.0.1' => 3600,
        ], $this->ttls);

        $this->expectException(TooManyRequestsHttpException::class);
        $this->request($filter, '10.0.0.1');
    }

    /**
     * @param array<int, int> $limits
     */
    private function createFilter(array $limits): IpRateLimiter {
        return new IpRateLimiter([
            'keyPrefix' => 'test',
            'limits' => $limits,
        ]);
    }

    private function request(IpRateLimiter $filter, string $ip): bool {
        $request = $this->createMock(Request::class);
        $request->method('getUserIP')->willReturn($ip);
        Yii::$app->set('request', $request);

        return $filter->beforeAction(new Action('test', $this->createMock(Controller::class)));
    }

}
