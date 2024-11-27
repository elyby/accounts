<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\components\Tokens\Component;
use api\tests\unit\TestCase;
use Generator;
use InvalidArgumentException;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Parser;
use Yii;

class ComponentTest extends TestCase {

    /**
     * @var Component
     */
    private Component $component;

    public function testCreate() {
        // Run without any arguments
        $token = $this->component->create();
        $this->assertSame('ES256', $token->getHeader('alg'));
        $this->assertEmpty(array_diff(array_keys($token->getClaims()), ['iat', 'exp']));
        $this->assertEqualsWithDelta(time(), $token->getClaim('iat'), 1);

        // Pass exp claim
        $time = time() + 60;
        $token = $this->component->create(['exp' => $time]);
        $this->assertSame($time, $token->getClaim('exp'));

        // Pass custom payloads
        $token = $this->component->create(['find' => 'me']);
        $this->assertArrayHasKey('find', $token->getClaims());
        $this->assertSame('me', $token->getClaim('find'));

        // Pass custom headers
        $token = $this->component->create([], ['find' => 'me']);
        $this->assertArrayHasKey('find', $token->getHeaders());
        $this->assertSame('me', $token->getHeader('find'));
    }

    public function testParse() {
        // Valid token signed with ES256
        $token = $this->component->parse('eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.M8Kam9bv0BXui3k7Posq_vc0I95Kb_Tw7L2vPdEPlwsHqh1VJHoWtlQc32_SlsotttL7j6RYbffBkRFX2wDGFQ');
        $this->assertValidParsedToken($token, 'ES256');

        // Valid token signed with ES256, but the signature is invalid
        $token = $this->component->parse('eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.xxx');
        $this->assertValidParsedToken($token, 'ES256');

        // Completely invalid token
        $this->expectException(InvalidArgumentException::class);
        $this->component->parse('How do you tame a horse in Minecraft?');
    }

    /**
     * @dataProvider getVerifyCases
     */
    public function testVerify(Token $token, bool $shouldBeValid) {
        //TODO $this->assertSame($shouldBeValid, $this->component->verify($token));
    }

    public static function getVerifyCases(): Generator
    {
        /*TODO fix $parser = new Parser(new JoseEncoder());
        yield 'ES256' => [
            $parser->parse('eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.M8Kam9bv0BXui3k7Posq_vc0I95Kb_Tw7L2vPdEPlwsHqh1VJHoWtlQc32_SlsotttL7j6RYbffBkRFX2wDGFQ'),
            true,
        ];
        yield 'ES256 with an invalid signature' => [
            $parser->parse('eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.xxx'),
            false,
        ];
        yield 'RS256 (unsupported)' => [
            $parser->parse('eyJhbGciOiJSUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.t3c68OMaoWWXxNFuz6SW-RfNmCOwAagyPSedbzJ1K3gR3bY5C8PRP6IEyE-OQvAcSFQcake0brsa4caXAmVlU0c3jQxpjk0bl4fBMd-InpGCoo42G89lgAY-dqWeJqokRORCpUL5Mzptbm5fNDlCrnNhI_6EmQygL3WXh1uorCbcxxO-Lb2Nr7Sge7GV0t24-I61I7ErrFL2ZC9ybSi6V8pdhFZlfO6MSUM0ASyRN994sVmcQEZHDiQFP7zj79zoAFamfYe8JBFAGtC-p4LeVYjrw052VahNXyRuGLxW7y1gX-znpyx0T-7lgKSWVxhJ6k3qt5qT33utdC76w1vihEdYinpEE3VbTMN01bxAFpyDbK11R49FCwCKStPjw_wdoLZChx_zob95yVU6IUCJwPYVc4SBtrAPV0uVe3mL3Gzgtr6MkhJAF3diFevTLGfnOOCAWwhdjVs10VWqcajBwvfFlm_Yw5MYZnetEECqumqFEr_u6CdRxtx0gCiPReDG8XwYHt0EqEw-LoRqxGWp5zqfud7f0DWv6cXlLbnKsB8XQh8EqnKblvNCFilXJIgfknCZ34PAob1pUkXO1geMLw4b8NUnKta1D3ad3AxGW5CEmOjWzEhzMOxIgnouU2ZVtWFDrPVs12Q4494BxTvGKXrG2cT6TK18-XY26DllglY'),
            false,
        ];*/
        yield [];
    }

    protected function _setUp(): void
    {
        parent::_setUp();
        $this->component = Yii::$app->tokens;
    }

    private function assertValidParsedToken(Token $token, string $expectedAlg): void
    {
        $this->assertSame($expectedAlg, $token->getHeader('alg'));
        $this->assertSame(1564527476, $token->getClaim('iat'));
        $this->assertSame(1564531076, $token->getClaim('exp'));
        $this->assertSame('ely|1', $token->getClaim('sub'));
        $this->assertSame(3069592, $token->getClaim('jti'));
        $this->assertSame('accounts_web_user', $token->getClaim('ely-scopes'));
    }

}
