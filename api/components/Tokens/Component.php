<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Carbon\Carbon;
use Exception;
use InvalidArgumentException;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use ParagonIE\ConstantTime\Base64UrlSafe;
use RangeException;
use SodiumException;
use Webmozart\Assert\Assert;
use yii\base\Component as BaseComponent;

class Component extends BaseComponent {

    private const string PREFERRED_ALGORITHM = 'ES256';

    /**
     * @var string
     */
    public string $privateKeyPath;

    /**
     * @var string|null
     */
    public ?string $privateKeyPass;

    /**
     * @var string
     */
    public string $encryptionKey;

    private ?AlgorithmsManager $algorithmManager = null;

    public function init(): void {
        parent::init();
        Assert::notEmpty($this->privateKeyPath, 'privateKeyPath must be set');
        Assert::notEmpty($this->encryptionKey, 'encryptionKey must be set');
    }

    /**
     * @param array{
     *     sub?: string,
     *     jti?: string,
     *     iat?: \DateTimeImmutable,
     *     nbf?: \DateTimeImmutable,
     *     exp?: \DateTimeImmutable,
     * } $payloads
     * @param array $headers
     *
     * @throws \api\components\Tokens\AlgorithmIsNotDefinedException
     */
    public function create(array $payloads = [], array $headers = []): UnencryptedToken {
        $now = Carbon::now();
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))->issuedAt($now->toDateTimeImmutable());
        if (isset($payloads['sub'])) {
            $builder = $builder->relatedTo($payloads['sub']);
        }

        if (isset($payloads['jti'])) {
            $builder = $builder->identifiedBy($payloads['jti']);
        }

        if (isset($payloads['iat'])) {
            $builder = $builder->issuedAt($payloads['iat']);
        }

        if (isset($payloads['nbf'])) {
            $builder = $builder->canOnlyBeUsedAfter($payloads['nbf']);
        }

        if (isset($payloads['exp'])) {
            $builder = $builder->expiresAt($payloads['exp']);
        }

        foreach ($payloads as $claim => $value) {
            if (!in_array($claim, RegisteredClaims::ALL, true)) { // Registered claims are handled by the if-chain above
                $builder = $builder->withClaim($claim, $this->prepareValue($value));
            }
        }

        foreach ($headers as $claim => $value) {
            $builder = $builder->withHeader($claim, $this->prepareValue($value));
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $algorithm = $this->getAlgorithmManager()->get(self::PREFERRED_ALGORITHM);

        return $builder->getToken($algorithm->getSigner(), $algorithm->getPrivateKey());
    }

    /**
     * @param string $jwt
     *
     * @return Token
     * @throws InvalidArgumentException
     */
    public function parse(string $jwt): Token {
        return (new Parser(new JoseEncoder()))->parse($jwt);
    }

    public function verify(Token $token): bool {
        try {
            $algorithm = $this->getAlgorithmManager()->get($token->headers()->get('alg'));
            return (new Validator())->validate($token, new SignedWith($algorithm->getSigner(), $algorithm->getPublicKey()));
        } catch (Exception) {
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
     * @throws SodiumException
     * @throws RangeException
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
        return $this->getAlgorithmManager()->get(self::PREFERRED_ALGORITHM)->getPublicKey()->contents();
    }

    private function getAlgorithmManager(): AlgorithmsManager {
        if ($this->algorithmManager === null) {
            $this->algorithmManager = new AlgorithmsManager([
                new Algorithms\ES256("file://{$this->privateKeyPath}", $this->privateKeyPass),
            ]);
        }

        return $this->algorithmManager;
    }

    private function prepareValue(EncryptedValue|string $value): string {
        if ($value instanceof EncryptedValue) {
            return $this->encryptValue($value->value);
        }

        return $value;
    }

}
