<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\components\Tokens\Component;
use api\components\Tokens\InvalidTokenException;
use api\components\Tokens\TokenExpiredException;
use api\components\Tokens\TokenRevokedException;
use api\tests\unit\TestCase;
use Carbon\Carbon;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\OauthClientFixture;
use common\tests\fixtures\OauthSessionFixture;
use DateTimeImmutable;
use Yii;

class ComponentTest extends TestCase {

    /**
     * @var Component
     */
    private Component $component;

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'oauthClients' => OauthClientFixture::class,
            'oauthSessions' => OauthSessionFixture::class,
        ];
    }

    public function testCreate(): void {
        // Run without any arguments
        $token = $this->component->create();
        $this->assertSame('ES256', $token->headers()->get('alg'));
        $this->assertSame('3XawksbcGUZ-dZsrvmNbIdJB515WCxWbVNH1Xcpe7MQ', $token->headers()->get('kid'));
        $this->assertEmpty(array_diff(array_keys($token->claims()->all()), ['iat', 'exp']));
        $this->assertEqualsWithDelta(time(), $token->claims()->get('iat')->getTimestamp(), 1);

        // Pass exp claim
        $time = time() + 60;
        $token = $this->component->create(['exp' => new DateTimeImmutable("@{$time}", null)]);
        $this->assertSame($time, $token->claims()->get('exp')->getTimestamp());

        // Pass custom payloads
        $token = $this->component->create(['find' => 'me']);
        $this->assertArrayHasKey('find', $token->claims()->all());
        $this->assertSame('me', $token->claims()->get('find'));

        // Pass custom headers
        $token = $this->component->create([], ['find' => 'me']);
        $this->assertArrayHasKey('find', $token->headers()->all());
        $this->assertSame('me', $token->headers()->get('find'));
    }

    public function testGetJwks(): void {
        // The expected values are calculated from the test key independently via the openssl CLI
        $this->assertSame([
            'keys' => [
                [
                    'kty' => 'EC',
                    'crv' => 'P-256',
                    'x' => 'S2Pyq9r0CyyviLaWwq0ki5uy8hr_ZbNO--3j4XP43uI',
                    'y' => 'w_fxmJKyhiEZfh7uR0_i8Gb6xXBGlYT5OQr7VeM5WCY',
                    'use' => 'sig',
                    'alg' => 'ES256',
                    'kid' => '3XawksbcGUZ-dZsrvmNbIdJB515WCxWbVNH1Xcpe7MQ',
                ],
            ],
        ], $this->component->getJwks());
    }

    /**
     * @param class-string<InvalidTokenException>|null $expectedException
     * @dataProvider getVerifyAccessTokenCases
     */
    public function testVerifyAccessToken(string $jwt, bool $verifyExpiration, ?string $expectedException): void {
        Carbon::setTestNow(Carbon::create(2019, 8, 1, 1, 2, 22, 'Europe/Minsk'));
        $exception = null;
        try {
            $token = $this->component->verifyAccessToken($jwt, verifyExpiration: $verifyExpiration);
            $this->assertSame($jwt, $token->toString());
        } catch (InvalidTokenException $e) {
            $exception = $e;
        }

        $this->assertSame($expectedException, $exception === null ? null : $exception::class);
    }

    /**
     * @return iterable<string, array{string, bool, class-string<InvalidTokenException>|null}>
     */
    public static function getVerifyAccessTokenCases(): iterable {
        $valid = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ2MTA1NDIsImV4cCI6MTU2NDYxNDE0Miwic3ViIjoiZWx5fDEifQ.4Oidvuo4spvUf9hkpHR72eeqZUh2Zbxh_L8Od3vcgTj--0iOrcOEp6zwmEW6vF7BTHtjz2b3mXce61bqsCjXjQ';
        $expired = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ2MDMzNDIsImV4cCI6MTU2NDYwNjk0Miwic3ViIjoiZWx5fDEifQ.36cDWyiXRArv-lgK_S5dyC5m_Ddytwkb78tMrxcPcbWEpoeg2VtwPC7zr6NI0cd0CuLw6InC2hZ9Ey95SSOsHw';
        $iatFromFuture = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ2MTc3NDIsImV4cCI6MTU2NDYxNDE0Miwic3ViIjoiZWx5fDEifQ._6hj6XUSmSLibgT9ZE1Pokf4oI9r-d6tEc1z2J-fBlr1710Qiso5yNcXqb3Z_xy7Qtemyq8jOlOZA8DvmkVBrg';
        $revokedByOauthClient = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudF9pbmZvLG1pbmVjcmFmdF9zZXJ2ZXJfc2Vzc2lvbiIsImlhdCI6MTU2NDYxMDUwMCwic3ViIjoiZWx5fDEiLCJjbGllbnRfaWQiOiJ0bGF1bmNoZXIifQ.qmiPOjI8jGAQdP5LoAVHO8L75Ly7fRcrTB_iYsUgQ4azgsPnLEhvG7dUnQ9utEd3RK5swDpaZ0bXf90vRbvnmg';
        $revokedMinecraft = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoibWluZWNyYWZ0X3NlcnZlcl9zZXNzaW9uIiwiZWx5LWNsaWVudC10b2tlbiI6IllBTVhneTBBcEI5Z2dUX1VYNjNJaTdKcGtNd2ZwTmxaaE8yVVVEeEZ3YTFmZ2g4dksyN0RtV25vN2xqbk1pWWJwQ1VuS09YVnR2V1YtVVg1dWRQVVFsLU4xY3BBZlJBX2EtZW1BZyIsImlhdCI6MTU2NDYxMDUwMCwic3ViIjoiZWx5fDEifQ.LtE9cQJ4z5dGVkDZl50M2HZH6kOYHgGz2RIycS_lzU9YLhosQ3ux7i2KI7qGI7BNuxO5zJ1OkxF2r9Qc240EpA';
        $invalidSignature = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ2MTA1NDIsImV4cCI6MTU2NDYxNDE0Miwic3ViIjoiZWx5fDEifQ.yth31f2PyhUkYSfBlizzUXWIgOvxxk8gNP-js0z8g1OT5rig40FPTIkgsZRctAwAAlj6QoIWW7-hxLTcSb2vmw';

        yield 'valid' => [$valid, true, null];
        yield 'expired' => [$expired, true, TokenExpiredException::class];
        yield 'expired without expiration verification' => [$expired, false, null];
        yield 'iat from future' => [$iatFromFuture, true, InvalidTokenException::class];
        yield 'iat from future without expiration verification' => [$iatFromFuture, false, InvalidTokenException::class];
        yield 'revoked by oauth client' => [$revokedByOauthClient, true, TokenRevokedException::class];
        yield 'revoked by unauthorized minecraft launcher' => [$revokedMinecraft, true, TokenRevokedException::class];
        yield 'revoked by unauthorized minecraft launcher without expiration verification' => [$revokedMinecraft, false, TokenRevokedException::class];
        yield 'invalid signature' => [$invalidSignature, true, InvalidTokenException::class];
        yield 'empty token' => ['', true, InvalidTokenException::class];
        yield 'not a jwt' => ['How do you tame a horse in Minecraft?', true, InvalidTokenException::class];
        yield 'malformed signature' => [
            'eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.xxx',
            false,
            InvalidTokenException::class,
        ];
        yield 'unsupported algorithm' => [
            'eyJhbGciOiJSUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.t3c68OMaoWWXxNFuz6SW-RfNmCOwAagyPSedbzJ1K3gR3bY5C8PRP6IEyE-OQvAcSFQcake0brsa4caXAmVlU0c3jQxpjk0bl4fBMd-InpGCoo42G89lgAY-dqWeJqokRORCpUL5Mzptbm5fNDlCrnNhI_6EmQygL3WXh1uorCbcxxO-Lb2Nr7Sge7GV0t24-I61I7ErrFL2ZC9ybSi6V8pdhFZlfO6MSUM0ASyRN994sVmcQEZHDiQFP7zj79zoAFamfYe8JBFAGtC-p4LeVYjrw052VahNXyRuGLxW7y1gX-znpyx0T-7lgKSWVxhJ6k3qt5qT33utdC76w1vihEdYinpEE3VbTMN01bxAFpyDbK11R49FCwCKStPjw_wdoLZChx_zob95yVU6IUCJwPYVc4SBtrAPV0uVe3mL3Gzgtr6MkhJAF3diFevTLGfnOOCAWwhdjVs10VWqcajBwvfFlm_Yw5MYZnetEECqumqFEr_u6CdRxtx0gCiPReDG8XwYHt0EqEw-LoRqxGWp5zqfud7f0DWv6cXlLbnKsB8XQh8EqnKblvNCFilXJIgfknCZ34PAob1pUkXO1geMLw4b8NUnKta1D3ad3AxGW5CEmOjWzEhzMOxIgnouU2ZVtWFDrPVs12Q4494BxTvGKXrG2cT6TK18-XY26DllglY',
            false,
            InvalidTokenException::class,
        ];
    }

    protected function _setUp(): void {
        parent::_setUp();
        $this->component = Yii::$app->tokens;
    }

    protected function _tearDown(): void {
        Carbon::setTestNow();
        parent::_tearDown();
    }

}
