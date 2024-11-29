<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\components\Tokens\TokenReader;
use api\tests\unit\TestCase;
use DateTimeImmutable;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Blake2b;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Builder;

class TokenReaderTest extends TestCase {

    /**
     * @dataProvider getAccountIdTestCases
     */
    public function testGetAccountId(array $claims, $expectedResult) {
        $this->assertSame($expectedResult, $this->createReader($claims)->getAccountId());
    }

    public function getAccountIdTestCases() {
        yield [['sub' => 'ely|1'], 1];
        yield [['sub' => '1'], null];
        yield [['sub' => 'ely-login|1'], null];
        yield [[], null];
    }

    /**
     * @dataProvider getClientIdTestCases
     */
    public function testGetClientId(array $claims, $expectedResult) {
        $this->assertSame($expectedResult, $this->createReader($claims)->getClientId());
    }

    public function getClientIdTestCases() {
        yield [['client_id' => 'find-me'], 'find-me'];
        yield [[], null];
    }

    /**
     * @dataProvider getScopesTestCases
     */
    public function testGetScopes(array $claims, $expectedResult) {
        $this->assertSame($expectedResult, $this->createReader($claims)->getScopes());
    }

    public function getScopesTestCases() {
        yield [['scope' => 'scope1 scope2'], ['scope1', 'scope2']];
        yield [['ely-scopes' => 'scope1,scope2'], ['scope1', 'scope2']];
        yield [[], null];
    }

    /**
     * @dataProvider getMinecraftClientTokenTestCases
     */
    public function testGetMinecraftClientToken(array $claims, $expectedResult) {
        $this->assertSame($expectedResult, $this->createReader($claims)->getMinecraftClientToken());
    }

    public function getMinecraftClientTokenTestCases() {
        yield [['ely-client-token' => 'GPZiBFlJld30KfGTe-E2yITKbfJYmWFA6Ky5CsllnIsVdmswMu_PXNdYnQGexF_CkXiuOQd1smrO3S4'], 'aaaaa-aaa-aaa-aaaaa'];
        yield [[], null];
    }

    private function createReader(array $claims): TokenReader {
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));

        foreach ($claims as $key => $value) {
            if ($key == 'sub') {
                $builder = $builder->relatedTo($value);
            } else {
                $builder = $builder->withClaim($key, $value);
            }
        }

        return new TokenReader($builder->getToken(new Blake2b(), InMemory::plainText('MpQd6dDPiqnzFSWmpUfLy4+Rdls90Ca4C8e0QD0IxqY=')));
    }

}
