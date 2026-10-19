<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha\Providers\Altcha;

use AltchaOrg\Altcha\Algorithm\Pbkdf2;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\HmacAlgorithm;
use api\components\Captcha\Providers\Altcha\OpensslPbkdf2;
use api\tests\unit\TestCase;

final class OpensslPbkdf2Test extends TestCase {

    /**
     * @dataProvider hmacAlgorithmsProvider
     */
    public function testSameAsLibraryImplementation(HmacAlgorithm $hmacAlgorithm): void {
        $library = new Pbkdf2($hmacAlgorithm);
        $openssl = new OpensslPbkdf2($hmacAlgorithm);

        $this->assertSame($library->getAlgorithmName(), $openssl->getAlgorithmName());

        $salt = random_bytes(16);
        $password = random_bytes(16) . pack('N', 12345);
        foreach ([[1, 32], [1000, 32], [1000, 16], [1000, 64]] as [$cost, $keyLength]) {
            $parameters = new ChallengeParameters(algorithm: $library->getAlgorithmName(), nonce: '', salt: '', cost: $cost, keyLength: $keyLength);
            $this->assertSame(
                bin2hex($library->deriveKey($parameters, $salt, $password)->derivedKey),
                bin2hex($openssl->deriveKey($parameters, $salt, $password)->derivedKey),
                "cost={$cost}, keyLength={$keyLength}",
            );
        }
    }

    /**
     * @return iterable<string, array{HmacAlgorithm}>
     */
    public static function hmacAlgorithmsProvider(): iterable {
        foreach (HmacAlgorithm::cases() as $case) {
            yield $case->value => [$case];
        }
    }

}
