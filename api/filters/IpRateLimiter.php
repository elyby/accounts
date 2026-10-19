<?php
declare(strict_types=1);

namespace api\filters;

use Webmozart\Assert\Assert;
use Yii;
use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

/**
 * Limits the number of requests from a single IP address with fixed windows stored in Redis.
 * IPv6 addresses are limited per /64 subnet, since a single client usually owns the whole subnet.
 */
final class IpRateLimiter extends ActionFilter {

    /**
     * Increments the counter and sets its TTL in a single atomic operation,
     * so a counter can't be left without expiration (which would block the IP forever)
     */
    private const string INCR_SCRIPT = <<<'LUA'
        local count = redis.call('INCR', KEYS[1])
        if count == 1 then
            redis.call('EXPIRE', KEYS[1], ARGV[1])
        end
        return count
        LUA;

    /**
     * @var array<int, int> window duration in seconds => max number of requests within the window
     */
    public array $limits = [];

    public string $keyPrefix = '';

    public function init(): void {
        parent::init();
        Assert::notEmpty($this->limits, 'At least one limit must be configured');
        Assert::stringNotEmpty($this->keyPrefix, 'keyPrefix must be configured');
    }

    /**
     * @throws TooManyRequestsHttpException
     */
    public function beforeAction($action): bool {
        $ip = self::normalizeIp(Yii::$app->getRequest()->getUserIP());
        $isExceeded = false;
        // Every window is incremented even if one of them is already exceeded,
        // so that the requests made while being blocked count in the longer windows too
        foreach ($this->limits as $window => $limit) {
            $count = (int)Yii::$app->redis->executeCommand('EVAL', [
                self::INCR_SCRIPT,
                1,
                "{$this->keyPrefix}:{$window}:{$ip}",
                $window,
            ]);
            if ($count > $limit) {
                $isExceeded = true;
            }
        }

        if ($isExceeded) {
            throw new TooManyRequestsHttpException();
        }

        return parent::beforeAction($action);
    }

    private static function normalizeIp(?string $ip): string {
        if ($ip === null) {
            return 'unknown';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            /** @var string $binary validated above */
            $binary = inet_pton($ip);

            return inet_ntop(substr($binary, 0, 8) . str_repeat("\0", 8)) . '/64';
        }

        return $ip;
    }

}
