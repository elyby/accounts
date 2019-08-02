<?php
declare(strict_types=1);

namespace api\components\Tokens\Algorithms;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;

interface AlgorithmInterface {

    public function getAlgorithmId(): string;

    public function getSigner(): Signer;

    public function getPrivateKey(): Key;

    public function getPublicKey(): Key;

}
