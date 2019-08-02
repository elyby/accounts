<?php
declare(strict_types=1);

namespace api\components\Tokens\Algorithms;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class HS256 implements AlgorithmInterface {

    /**
     * @var string
     */
    private $key;

    /**
     * @var Key|null
     */
    private $loadedKey;

    public function __construct(string $key) {
        $this->key = $key;
    }

    public function getAlgorithmId(): string {
        return 'HS256';
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
            $this->loadedKey = new Key($this->key);
        }

        return $this->loadedKey;
    }

}
