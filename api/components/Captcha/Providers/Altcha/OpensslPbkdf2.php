<?php
declare(strict_types=1);

namespace api\components\Captcha\Providers\Altcha;

use AltchaOrg\Altcha\Algorithm\DeriveKeyInterface;
use AltchaOrg\Altcha\Algorithm\DeriveKeyResult;
use AltchaOrg\Altcha\ChallengeParameters;
use AltchaOrg\Altcha\HmacAlgorithm;
use RuntimeException;

/**
 * A drop-in replacement for the library's {@see \AltchaOrg\Altcha\Algorithm\Pbkdf2}:
 * the same algorithm and the same result, but computed by OpenSSL, which is ~18x faster
 * than hash_pbkdf2(). It matters because the challenge issuing endpoint is public and
 * calculates the KDF once per request.
 */
final readonly class OpensslPbkdf2 implements DeriveKeyInterface {

    public function __construct(
        private HmacAlgorithm $hmacAlgorithm = HmacAlgorithm::SHA256,
    ) {
    }

    /**
     * Must match the library's implementation: the name is passed in the challenge
     * and the client side chooses the KDF by it.
     */
    public function getAlgorithmName(): string {
        return 'PBKDF2/' . $this->hmacAlgorithm->value;
    }

    public function deriveKey(ChallengeParameters $parameters, string $salt, string $password): DeriveKeyResult {
        $derivedKey = openssl_pbkdf2(
            password: $password,
            salt: $salt,
            key_length: max(1, $parameters->keyLength),
            iterations: max(1, $parameters->cost),
            digest_algo: $this->hmacAlgorithm->hashAlgo(),
        );
        if ($derivedKey === false) {
            throw new RuntimeException('Unable to derive the key with OpenSSL: ' . openssl_error_string());
        }

        return new DeriveKeyResult($derivedKey);
    }

}
