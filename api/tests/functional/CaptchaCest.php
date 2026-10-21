<?php
declare(strict_types=1);

namespace api\tests\functional;

use api\tests\FunctionalTester;
use Yii;

final class CaptchaCest {

    public function _before(FunctionalTester $I): void {
        $this->resetRateLimit();
    }

    /**
     * The rate limit test exhausts the limit, which would break the next tests requesting challenges
     */
    public function _after(FunctionalTester $I): void {
        $this->resetRateLimit();
    }

    public function testAltchaChallenge(FunctionalTester $I): void {
        $I->wantTo('get a new altcha challenge without authorization');
        $I->sendGET('/api/captcha/altcha/challenge');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeHttpHeader('Cache-Control', 'no-store');
        $I->canSeeResponseContainsJson([
            'parameters' => [
                'algorithm' => 'PBKDF2/SHA-256',
                'data' => [
                    'counterMax' => 10,
                ],
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.signature');
        $I->canSeeResponseJsonMatchesJsonPath('$.parameters.keySignature');
        $I->canSeeResponseJsonMatchesJsonPath('$.parameters.expiresAt');
        $firstSignature = $I->grabDataFromResponseByJsonPath('$.signature')[0];

        $I->wantTo('get a different challenge on the next request');
        $I->sendGET('/api/captcha/altcha/challenge');
        $I->canSeeResponseCodeIs(200);
        $I->assertNotSame($firstSignature, $I->grabDataFromResponseByJsonPath('$.signature')[0]);
    }

    public function testRateLimit(FunctionalTester $I): void {
        $I->wantTo('get 429 after exceeding the per minute limit');
        for ($i = 0; $i < 30; $i++) {
            $I->sendGET('/api/captcha/altcha/challenge');
            $I->canSeeResponseCodeIs(200);
        }

        $I->sendGET('/api/captcha/altcha/challenge');
        $I->canSeeResponseCodeIs(429);
    }

    private function resetRateLimit(): void {
        // Rate limiter counters live in Redis and would leak between the tests otherwise.
        // KEYS is fine for the test Redis, but must never be used in the app code
        $keys = Yii::$app->redis->keys('captcha:altcha:challenge:ratelimit:*');
        if ($keys !== []) {
            Yii::$app->redis->del(...$keys);
        }
    }

}
