<?php
declare(strict_types=1);

namespace api\components\Tokens\Algorithms;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;

final class HS256 implements AlgorithmInterface {

    private ?InMemory $loadedKey = null;

    /**
     * @param non-empty-string $key
     */
    public function __construct(
        private readonly string $key,
    ) {
    }

    public function getSigner(): Signer {
        return new Sha256();
    }

    public function getPrivateKey(): Key {
        return $this->loadKey();
    }

    public function getPublicKey(): Key {
        return $this->loadKey();
    }

    private function loadKey(): Key {
        if ($this->loadedKey === null) {
            $this->loadedKey = InMemory::plainText($this->key);
        }

        return $this->loadedKey;
    }

}
