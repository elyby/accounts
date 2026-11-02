<?php
declare(strict_types=1);

namespace api\components\Tokens\Algorithms;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webmozart\Assert\Assert;

final class ES256 implements AlgorithmInterface {

    private ?Key $privateKey = null;

    private ?Key $publicKey = null;

    private ?string $keyId = null;

    private readonly Sha256 $signer;

    public function __construct(
        private readonly string $privateKeyPath,
        private readonly ?string $privateKeyPass = null,
    ) {
        $this->signer = new Sha256();
    }

    public function getSigner(): Signer {
        return $this->signer;
    }

    public function getPrivateKey(): Key {
        if ($this->privateKey === null) {
            $this->privateKey = InMemory::plainText($this->privateKeyPath, $this->privateKeyPass ?? '');
        }

        return $this->privateKey;
    }

    public function getPublicKey(): Key {
        if ($this->publicKey === null) {
            $privateKey = $this->getPrivateKey();
            $privateKeyOpenSSL = openssl_pkey_get_private($privateKey->contents(), $privateKey->passphrase());
            $publicPem = openssl_pkey_get_details($privateKeyOpenSSL)['key'];
            $this->publicKey = InMemory::plainText($publicPem);
        }

        return $this->publicKey;
    }

    /**
     * Public key in the JWK format (RFC 7518 section 6.2.1).
     *
     * @return array{
     *     kty: 'EC',
     *     crv: 'P-256',
     *     x: non-empty-string,
     *     y: non-empty-string,
     * }
     */
    public function getPublicJwk(): array {
        $publicKey = openssl_pkey_get_public($this->getPublicKey()->contents());
        Assert::notFalse($publicKey);
        $details = openssl_pkey_get_details($publicKey);
        Assert::isArray($details);
        Assert::same($details['ec']['curve_name'] ?? null, 'prime256v1');

        // The coordinates must be exactly 32 bytes long, but OpenSSL drops the leading zeros
        $x = Base64UrlSafe::encodeUnpadded(str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT));
        $y = Base64UrlSafe::encodeUnpadded(str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT));
        Assert::stringNotEmpty($x);
        Assert::stringNotEmpty($y);

        return [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => $x,
            'y' => $y,
        ];
    }

    /**
     * The key id is the JWK thumbprint (RFC 7638), so it doesn't require any configuration
     * and changes only with the key itself.
     */
    public function getKeyId(): string {
        if ($this->keyId === null) {
            $jwk = $this->getPublicJwk();
            // The required members in the lexicographic order without whitespaces (RFC 7638 section 3.2)
            $thumbprintInput = json_encode([
                'crv' => $jwk['crv'],
                'kty' => $jwk['kty'],
                'x' => $jwk['x'],
                'y' => $jwk['y'],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

            $this->keyId = Base64UrlSafe::encodeUnpadded(hash('sha256', $thumbprintInput, true));
        }

        return $this->keyId;
    }

}
