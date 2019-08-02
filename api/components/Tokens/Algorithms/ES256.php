<?php
declare(strict_types=1);

namespace api\components\Tokens\Algorithms;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key;

class ES256 implements AlgorithmInterface {

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var string|null
     */
    private $privateKeyPass;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var Key|null
     */
    private $loadedPrivateKey;

    /**
     * @var Key|null
     */
    private $loadedPublicKey;

    /**
     * TODO: document arguments
     *
     * @param string $privateKey
     * @param string|null $privateKeyPass
     * @param string $publicKey
     */
    public function __construct(string $privateKey, ?string $privateKeyPass, string $publicKey) {
        $this->privateKey = $privateKey;
        $this->privateKeyPass = $privateKeyPass;
        $this->publicKey = $publicKey;
    }

    public function getAlgorithmId(): string {
        return 'ES256';
    }

    public function getSigner(): Signer {
        return new Sha256();
    }

    public function getPrivateKey(): Key {
        if ($this->loadedPrivateKey === null) {
            $this->loadedPrivateKey = new Key($this->privateKey, $this->privateKeyPass);
        }

        return $this->loadedPrivateKey;
    }

    public function getPublicKey(): Key {
        if ($this->loadedPublicKey === null) {
            $this->loadedPublicKey = new Key($this->publicKey);
        }

        return $this->loadedPublicKey;
    }

}
