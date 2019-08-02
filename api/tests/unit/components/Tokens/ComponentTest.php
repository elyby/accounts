<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\tests\unit\TestCase;
use InvalidArgumentException;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Yii;

class ComponentTest extends TestCase {

    /**
     * @var \api\components\Tokens\Component
     */
    private $component;

    public function testCreate() {
        // Run without any arguments
        $token = $this->component->create();
        $this->assertSame('ES256', $token->getHeader('alg'));
        $this->assertEmpty(array_diff(array_keys($token->getClaims()), ['iat', 'exp']));
        $this->assertEqualsWithDelta(time(), $token->getClaim('iat'), 1);
        $this->assertEqualsWithDelta(time() + 3600, $token->getClaim('exp'), 2);

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
        // Valid token signed with HS256
        $token = $this->component->parse('eyJhbGciOiJIUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.ixapBbhaUCejbcPTnFi5nqk75XKd1_lQJd1ZPgGTLEc');
        $this->assertValidParsedToken($token, 'HS256');

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
        $this->assertSame($shouldBeValid, $this->component->verify($token));
    }

    public function getVerifyCases() {
        yield 'HS256' => [
            (new Parser())->parse('eyJhbGciOiJIUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.ixapBbhaUCejbcPTnFi5nqk75XKd1_lQJd1ZPgGTLEc'),
            true,
        ];
        yield 'ES256' => [
            (new Parser())->parse('eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.M8Kam9bv0BXui3k7Posq_vc0I95Kb_Tw7L2vPdEPlwsHqh1VJHoWtlQc32_SlsotttL7j6RYbffBkRFX2wDGFQ'),
            true,
        ];
        yield 'ES256 with an invalid signature' => [
            (new Parser())->parse('eyJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ1Mjc0NzYsImV4cCI6MTU2NDUzMTA3Niwic3ViIjoiZWx5fDEiLCJqdGkiOjMwNjk1OTJ9.xxx'),
            false,
        ];
    }

    protected function _setUp() {
        parent::_setUp();
        $this->component = Yii::$app->tokens;
    }

    private function assertValidParsedToken(Token $token, string $expectedAlg) {
        $this->assertSame($expectedAlg, $token->getHeader('alg'));
        $this->assertSame(1564527476, $token->getClaim('iat'));
        $this->assertSame(1564531076, $token->getClaim('exp'));
        $this->assertSame('ely|1', $token->getClaim('sub'));
        $this->assertSame(3069592, $token->getClaim('jti'));
        $this->assertSame('accounts_web_user', $token->getClaim('ely-scopes'));
    }

}
