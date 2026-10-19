<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha\Providers\Altcha;

use api\components\Captcha\Providers\Altcha\UsedChallengesStorage;
use api\tests\unit\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use yii\redis\Connection;
use yii\redis\SocketException;

final class UsedChallengesStorageTest extends TestCase {

    private const int NOW = 1792436000;

    private Connection&MockObject $redis;

    private UsedChallengesStorage $storage;

    protected function setUp(): void {
        parent::setUp();
        Carbon::setTestNow(Carbon::createFromTimestamp(self::NOW));
        $this->redis = $this->getMockBuilder(Connection::class)
            ->onlyMethods(['executeCommand'])
            ->getMock();
        $this->storage = new UsedChallengesStorage($this->redis);
    }

    protected function tearDown(): void {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testFirstUsage(): void {
        $this->redis->expects($this->once())
            ->method('executeCommand')
            ->with('SET', ['captcha:altcha:used:signature', '1', 'EX', 605, 'NX'])
            ->willReturn(true);

        $this->assertTrue($this->storage->markUsed('signature', self::NOW + 600));
    }

    public function testRepeatedUsage(): void {
        // SET NX replies with nil when the key already exists
        $this->redis->method('executeCommand')->willReturn(null);

        $this->assertFalse($this->storage->markUsed('signature', self::NOW + 600));
    }

    /**
     * @dataProvider ttlProvider
     */
    public function testTtl(int|float $expiresAt, int $expectedTtl): void {
        $this->redis->expects($this->once())
            ->method('executeCommand')
            ->with('SET', $this->callback(fn(array $params): bool => $params[3] === $expectedTtl))
            ->willReturn(true);

        $this->storage->markUsed('signature', $expiresAt);
    }

    /**
     * @return iterable<string, array{int|float, int}>
     */
    public static function ttlProvider(): iterable {
        yield 'fractional expiresAt is rounded up' => [self::NOW + 600.2, 606];
        yield 'already expired challenge still gets a positive TTL' => [self::NOW - 100, 1];
    }

    public function testRedisFailureIsNotSuppressed(): void {
        $this->redis->method('executeCommand')->willThrowException(new SocketException('Connection refused'));

        $this->expectException(SocketException::class);
        $this->storage->markUsed('signature', self::NOW + 600);
    }

}
