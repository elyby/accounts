<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers;

use AltchaOrg\Altcha\Algorithm\DeriveKeyInterface;
use AltchaOrg\Altcha\Altcha as AltchaLib;
use AltchaOrg\Altcha\CreateChallengeOptions;
use AltchaOrg\Altcha\Payload as AltchaPayload;
use AltchaOrg\Altcha\VerifySolutionOptions;
use api\components\Captcha\Providers\Altcha\UsedChallengesStorage;
use Carbon\Carbon;
use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * @see https://altcha.org
 */
final readonly class Altcha implements ProviderInterface {

    public const string NAME = 'altcha';

    /**
     * The counter is packed as uint32 (counterMode: 'uint32' on the client side)
     */
    private const int MAX_COUNTER = 0xFFFFFFFF;

    /**
     * @param positive-int $cost the KDF cost. The server calculates the KDF once per issued challenge,
     *                           while the client's work is cost × counter
     * @param non-negative-int $counterMin the client finds the counter value by brute force, starting from 0
     * @param non-negative-int $counterMax
     * @param positive-int $ttl challenge lifetime in seconds
     * @param non-empty-string $challengeUrl
     */
    public function __construct(
        private AltchaLib $altcha,
        private DeriveKeyInterface $algorithm,
        private UsedChallengesStorage $usedChallenges,
        private int $cost,
        private int $counterMin,
        private int $counterMax,
        private int $ttl,
        private string $challengeUrl = '/api/captcha/altcha/challenge',
    ) {
        Assert::greaterThanEq($counterMin, 0);
        Assert::greaterThanEq($counterMax, $counterMin);
        Assert::lessThanEq($counterMax, self::MAX_COUNTER);
    }

    /**
     * @return array{
     *     challengeUrl: non-empty-string,
     * }
     */
    public function getPublicParams(): array {
        return [
            'challengeUrl' => $this->challengeUrl,
        ];
    }

    /**
     * Each call produces a new unique challenge
     *
     * @return array<string, mixed> the challenge, which is passed to the client as is
     */
    public function createChallenge(): array {
        return $this->altcha->createChallenge(new CreateChallengeOptions(
            algorithm: $this->algorithm,
            cost: $this->cost,
            counter: random_int($this->counterMin, $this->counterMax), // @phpstan-ignore argument.type (counterMin <= counterMax is asserted in the constructor)
            expiresAt: Carbon::now()->getTimestamp() + $this->ttl,
        ))->toArray();
    }

    public function verify(string $token, ?string $remoteIp): bool {
        try {
            $payload = AltchaPayload::fromBase64($token);
        } catch (InvalidArgumentException) {
            return false; // malformed
        }

        $result = $this->altcha->verifySolution(new VerifySolutionOptions($payload, $this->algorithm));
        if (!$result->verified) {
            return false; // expired, invalid signature or invalid solution
        }

        // The library treats a missing or zero expiresAt as "never expires".
        // The replay protection relies on the TTL, so such challenges are never accepted.
        $expiresAt = $payload->challenge->parameters->expiresAt;
        if (!$expiresAt) {
            return false;
        }

        // The signature can be trusted only after the successful verification.
        // Not the solution: on the fast verification path the counter isn't checked.
        /** @var string $signature verified signature always exists */
        $signature = $payload->challenge->signature;

        return $this->usedChallenges->markUsed($signature, $expiresAt);
    }

}
