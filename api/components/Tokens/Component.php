<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Carbon\Carbon;
use common\models\OauthClient;
use common\models\OauthSession;
use DateTimeImmutable;
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
use Throwable;
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
    public ?string $privateKeyPass = null;

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
     *     iss?: non-empty-string,
     *     aud?: non-empty-string,
     *     sub?: string,
     *     jti?: string,
     *     iat?: \DateTimeImmutable,
     *     nbf?: \DateTimeImmutable,
     *     exp?: \DateTimeImmutable,
     * } $payloads
     * @param array<non-empty-string, non-empty-string> $headers
     *
     * @throws \api\components\Tokens\AlgorithmIsNotDefinedException
     */
    public function create(array $payloads = [], array $headers = []): UnencryptedToken {
        $now = Carbon::now();
        $builder = (new Builder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates()))->issuedAt($now->toDateTimeImmutable());
        if (isset($payloads['iss'])) {
            $builder = $builder->issuedBy($payloads['iss']);
        }

        if (isset($payloads['aud'])) {
            $builder = $builder->permittedFor($payloads['aud']);
        }

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

        /** @noinspection PhpUnhandledExceptionInspection */
        $algorithm = $this->getAlgorithmManager()->get(self::PREFERRED_ALGORITHM);
        // Allows the token's consumers to find the key to verify the signature (see getJwks())
        if ($algorithm instanceof Algorithms\ES256) {
            $builder = $builder->withHeader('kid', $algorithm->getKeyId());
        }

        foreach ($headers as $claim => $value) {
            $builder = $builder->withHeader($claim, $this->prepareValue($value));
        }

        return $builder->getToken($algorithm->getSigner(), $algorithm->getPrivateKey());
    }

    /**
     * The single place where the access tokens are verified: the signature, the lifetime and the revocation.
     * The account's state (deleted, banned, etc.) is intentionally not checked here: some endpoints must accept
     * the tokens of banned/deleted accounts, so it's the caller's (or RBAC's) responsibility.
     *
     * @throws TokenExpiredException
     * @throws TokenRevokedException
     * @throws InvalidTokenException
     */
    public function verifyAccessToken(string $jwt, bool $verifyExpiration = true): UnencryptedToken {
        try {
            $token = $this->parse($jwt);
        } catch (Throwable $e) {
            throw new InvalidTokenException('Cannot parse the token', previous: $e);
        }

        if (!$token instanceof UnencryptedToken || !$this->verify($token)) {
            throw new InvalidTokenException('Invalid signature');
        }

        $now = Carbon::now();
        if ($verifyExpiration && $token->isExpired($now)) {
            throw new TokenExpiredException();
        }

        // Unlike the expiration, these are checked always: the revocation relies on the iat claim
        if (!$token->hasBeenIssuedBefore($now) || !$token->isMinimumTimeBefore($now)) {
            throw new InvalidTokenException('The token cannot be used yet');
        }

        if ($this->isRevoked($token)) {
            throw new TokenRevokedException();
        }

        return $token;
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

    /**
     * The public keys to verify the tokens signature in the JWK Set format (RFC 7517 section 5).
     *
     * @return array{
     *     keys: list<array<string, string>>,
     * }
     */
    public function getJwks(): array {
        $algorithm = $this->getAlgorithmManager()->get(self::PREFERRED_ALGORITHM);
        Assert::isInstanceOf($algorithm, Algorithms\ES256::class);

        return [
            'keys' => [
                array_merge($algorithm->getPublicJwk(), [
                    'use' => 'sig',
                    'alg' => self::PREFERRED_ALGORITHM,
                    'kid' => $algorithm->getKeyId(),
                ]),
            ],
        ];
    }

    /**
     * @param string $jwt
     *
     * @return Token
     * @throws InvalidArgumentException
     */
    private function parse(string $jwt): Token {
        return (new Parser(new JoseEncoder()))->parse($jwt);
    }

    private function verify(Token $token): bool {
        try {
            $algorithm = $this->getAlgorithmManager()->get($token->headers()->get('alg'));
            return (new Validator())->validate($token, new SignedWith($algorithm->getSigner(), $algorithm->getPublicKey()));
        } catch (Exception) {
            return false;
        }
    }

    private function getAlgorithmManager(): AlgorithmsManager {
        if ($this->algorithmManager === null) {
            $this->algorithmManager = new AlgorithmsManager([
                new Algorithms\ES256("file://{$this->privateKeyPath}", $this->privateKeyPass),
            ]);
        }

        return $this->algorithmManager;
    }

    private function isRevoked(UnencryptedToken $token): bool {
        $reader = new TokenReader($token);
        $accountId = $reader->getAccountId();
        // The client_credentials tokens aren't related to any account, so there is no session to check
        if ($accountId === null) {
            return false;
        }

        $clientIds = [];
        if ($reader->getMinecraftClientToken() !== null) {
            $clientIds[] = OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER;
        }

        if ($reader->getClientId() !== null) {
            $clientIds[] = $reader->getClientId();
        }

        /** @var DateTimeImmutable $iat */
        $iat = $token->claims()->get(RegisteredClaims::ISSUED_AT);
        foreach ($clientIds as $clientId) {
            $session = OauthSession::findOne(['account_id' => $accountId, 'client_id' => $clientId]);
            // The session is always created when the token is issued, so its absence means that it was revoked
            if ($session === null) {
                return true;
            }

            if ($session->isRevokedForTokenIssuedAt($iat)) {
                return true;
            }
        }

        return false;
    }

    private function prepareValue(EncryptedValue|string|bool $value): string|bool {
        if ($value instanceof EncryptedValue) {
            return $this->encryptValue($value->value);
        }

        return $value;
    }

}
