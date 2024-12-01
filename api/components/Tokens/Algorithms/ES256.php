<?php
declare(strict_types=1);

namespace api\components\Tokens\Algorithms;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;

final class ES256 implements AlgorithmInterface {

    private ?Key $privateKey = null;

    private ?Key $publicKey = null;

    private Sha256 $signer;

    public function __construct(
        private string $privateKeyPath,
        private ?string $privateKeyPass = null,
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

}
