<?php
declare(strict_types=1);

namespace api\tests\unit\components\Tokens;

use api\components\Tokens\TokenReader;
use api\tests\unit\TestCase;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Blake2b;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;

class TokenReaderTest extends TestCase {

    /**
     * @dataProvider getAccountIdTestCases
     */
    public function testGetAccountId(array $claims, ?int $expectedResult): void {
        $this->assertSame($expectedResult, $this->createReader($claims)->getAccountId());
    }

    public function getAccountIdTestCases(): iterable {
        yield [['sub' => 'ely|1'], 1];
        yield [['sub' => '1'], null];
        yield [['sub' => 'ely-login|1'], null];
        yield [[], null];
    }

    /**
     * @dataProvider getClientIdTestCases
     */
    public function testGetClientId(array $claims, ?string $expectedResult): void {
        $this->assertSame($expectedResult, $this->createReader($claims)->getClientId());
    }

    public function getClientIdTestCases(): iterable {
        yield [['client_id' => 'find-me'], 'find-me'];
        yield [[], null];
    }

    /**
     * @dataProvider getScopesTestCases
     */
    public function testGetScopes(array $claims, ?array $expectedResult): void {
        $this->assertSame($expectedResult, $this->createReader($claims)->getScopes());
    }

    public function getScopesTestCases(): iterable {
        yield [['scope' => 'scope1 scope2'], ['scope1', 'scope2']];
        yield [['ely-scopes' => 'scope1,scope2'], ['scope1', 'scope2']];
        yield [['scope' => ' scope1  scope2 '], ['scope1', 'scope2']];
        yield [['ely-scopes' => ',scope1,,scope2,'], ['scope1', 'scope2']];
        yield [['scope' => ' '], []];
        yield [[], null];
    }

    /**
     * @dataProvider hasScopeTestCases
     *
     * @param array<string, non-empty-string> $claims
     * @param non-empty-string $scope
     */
    public function testHasScope(array $claims, string $scope, bool $expectedResult): void {
        $this->assertSame($expectedResult, $this->createReader($claims)->hasScope($scope));
    }

    /**
     * @return iterable<string, array{array<string, non-empty-string>, non-empty-string, bool}>
     */
    public function hasScopeTestCases(): iterable {
        yield 'first scope' => [['scope' => 'scope1 scope2'], 'scope1', true];
        yield 'last scope' => [['scope' => 'scope1 scope2'], 'scope2', true];
        yield 'single scope' => [['scope' => 'scope1'], 'scope1', true];
        yield 'missing scope' => [['scope' => 'scope1 scope2'], 'scope3', false];
        yield 'partial match' => [['scope' => 'scope1 scope2'], 'scope', false];
        yield 'case sensitive' => [['scope' => 'scope1 scope2'], 'SCOPE1', false];
        yield 'legacy claim' => [['ely-scopes' => 'scope1,scope2'], 'scope2', true];
        yield 'legacy claim, missing scope' => [['ely-scopes' => 'scope1,scope2'], 'scope3', false];
        yield 'legacy claim is not delimited by space' => [['ely-scopes' => 'scope1 scope2'], 'scope2', false];
        yield 'scope claim takes precedence' => [['scope' => 'scope1', 'ely-scopes' => 'scope2'], 'scope2', false];
        yield 'no scopes claims' => [[], 'scope1', false];
    }

    /**
     * @dataProvider getMinecraftClientTokenTestCases
     */
    public function testGetMinecraftClientToken(array $claims, ?string $expectedResult): void {
        $this->assertSame($expectedResult, $this->createReader($claims)->getMinecraftClientToken());
    }

    public function getMinecraftClientTokenTestCases(): iterable {
        yield [['ely-client-token' => 'GPZiBFlJld30KfGTe-E2yITKbfJYmWFA6Ky5CsllnIsVdmswMu_PXNdYnQGexF_CkXiuOQd1smrO3S4'], 'aaaaa-aaa-aaa-aaaaa'];
        yield [[], null];
    }

    /**
     * @param array<string, non-empty-string> $claims
     */
    private function createReader(array $claims): TokenReader {
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));

        foreach ($claims as $key => $value) {
            if ($key === 'sub') {
                $builder = $builder->relatedTo($value);
            } else {
                $builder = $builder->withClaim($key, $value);
            }
        }

        return new TokenReader($builder->getToken(new Blake2b(), InMemory::plainText('MpQd6dDPiqnzFSWmpUfLy4+Rdls90Ca4C8e0QD0IxqY=')));
    }

}
