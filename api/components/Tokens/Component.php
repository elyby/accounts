<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Carbon\Carbon;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use yii\base\Component as BaseComponent;

class Component extends BaseComponent {

    private const PREFERRED_ALGORITHM = 'ES256';

    /**
     * @var string
     */
    public $hmacKey;

    /**
     * @var string
     */
    public $publicKeyPath;

    /**
     * @var string
     */
    public $privateKeyPath;

    /**
     * @var string|null
     */
    public $privateKeyPass;

    /**
     * @var AlgorithmsManager|null
     */
    private $algorithmManager;

    public function create(array $payloads = [], array $headers = []): Token {
        $now = Carbon::now();
        $builder = (new Builder())
            ->issuedAt($now->getTimestamp())
            ->expiresAt($now->addHour()->getTimestamp());
        foreach ($payloads as $claim => $value) {
            $builder->withClaim($claim, $value);
        }

        foreach ($headers as $claim => $value) {
            $builder->withHeader($claim, $value);
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $algorithm = $this->getAlgorithmManager()->get(self::PREFERRED_ALGORITHM);

        return $builder->getToken($algorithm->getSigner(), $algorithm->getPrivateKey());
    }

    /**
     * @param string $jwt
     *
     * @return Token
     * @throws \InvalidArgumentException
     */
    public function parse(string $jwt): Token {
        return (new Parser())->parse($jwt);
    }

    public function verify(Token $token): bool {
        try {
            $algorithm = $this->getAlgorithmManager()->get($token->getHeader('alg'));
            return $token->verify($algorithm->getSigner(), $algorithm->getPublicKey());
        } catch (Exception $e) {
            return false;
        }
    }

    private function getAlgorithmManager(): AlgorithmsManager {
        if ($this->algorithmManager === null) {
            $this->algorithmManager = new AlgorithmsManager([
                new Algorithms\HS256($this->hmacKey),
                new Algorithms\ES256(
                    "file://{$this->privateKeyPath}",
                    $this->privateKeyPass,
                    "file://{$this->publicKeyPath}"
                ),
            ]);
        }

        return $this->algorithmManager;
    }

}
