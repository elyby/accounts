<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Carbon\Carbon;
use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webmozart\Assert\Assert;
use yii\base\Component as BaseComponent;

class Component extends BaseComponent {

    private const PREFERRED_ALGORITHM = 'ES256';

    /**
     * @var string
     */
    public $privateKeyPath;

    /**
     * @var string|null
     */
    public $privateKeyPass;

    /**
     * @var string
     */
    public $encryptionKey;

    private ?AlgorithmsManager $algorithmManager = null;

    public function init(): void {
        parent::init();
        Assert::notEmpty($this->privateKeyPath, 'privateKeyPath must be set');
        Assert::notEmpty($this->encryptionKey, 'encryptionKey must be set');
    }

    public function create(array $payloads = [], array $headers = []): Token {
        $now = Carbon::now();
        $builder = (new Builder())->issuedAt($now->getTimestamp());
        if (isset($payloads['exp'])) {
            $builder->expiresAt($payloads['exp']);
        }

        foreach ($payloads as $claim => $value) {
            $builder->withClaim($claim, $this->prepareValue($value));
        }

        foreach ($headers as $claim => $value) {
            $builder->withHeader($claim, $this->prepareValue($value));
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

    public function encryptValue(string $rawValue): string {
        /** @noinspection PhpUnhandledExceptionInspection */
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = Base64UrlSafe::encodeUnpadded($nonce . sodium_crypto_secretbox($rawValue, $nonce, $this->encryptionKey));
        sodium_memzero($rawValue);

        return $cipher;
    }

    /**
     * @param string $encryptedValue
     *
     * @return string
     * @throws \SodiumException
     * @throws \RangeException
     */
    public function decryptValue(string $encryptedValue): string {
        $decoded = Base64UrlSafe::decode($encryptedValue);
        Assert::true(mb_strlen($decoded, '8bit') >= (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES));
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $cipherText = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $rawValue = sodium_crypto_secretbox_open($cipherText, $nonce, $this->encryptionKey);
        Assert::true($rawValue !== false);
        sodium_memzero($cipherText);

        return $rawValue;
    }

    public function getPublicKey(): string {
        return $this->getAlgorithmManager()->get(self::PREFERRED_ALGORITHM)->getPublicKey()->getContent();
    }

    private function getAlgorithmManager(): AlgorithmsManager {
        if ($this->algorithmManager === null) {
            $this->algorithmManager = new AlgorithmsManager([
                new Algorithms\ES256("file://{$this->privateKeyPath}", $this->privateKeyPass),
            ]);
        }

        return $this->algorithmManager;
    }

    private function prepareValue($value) {
        if ($value instanceof EncryptedValue) {
            return $this->encryptValue($value->getValue());
        }

        return $value;
    }

}
