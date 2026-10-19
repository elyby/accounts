<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers\Altcha;

use Carbon\Carbon;
use yii\redis\Connection as RedisConnection;

/**
 * ALTCHA library checks only the signature, the expiration and the solution itself,
 * so without this storage the same solution could be submitted any number of times until it expires.
 */
final readonly class UsedChallengesStorage {

    private const string KEY_PREFIX = 'captcha:altcha:used:';

    /**
     * A small reserve for the clock drift between the app servers
     */
    private const int TTL_RESERVE = 5;

    public function __construct(
        private RedisConnection $redis,
    ) {
    }

    /**
     * Atomically marks the challenge as used.
     *
     * The challenge signature must be passed only after a successful verification:
     * until then it's an untrusted user input. The solution must not be used as a key,
     * because on the fast verification path the counter isn't checked and can be changed freely.
     *
     * Redis errors aren't handled on purpose (fail closed).
     *
     * @param float $expiresAt unix timestamp of the challenge expiration, may be fractional
     *
     * @return bool true if the challenge is used for the first time
     */
    public function markUsed(string $challengeSignature, float $expiresAt): bool {
        $ttl = max(1, (int)ceil($expiresAt) - Carbon::now()->getTimestamp() + self::TTL_RESERVE);

        return $this->redis->set(self::KEY_PREFIX . $challengeSignature, '1', 'EX', $ttl, 'NX') === true;
    }

}
