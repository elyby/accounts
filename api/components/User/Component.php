<?php
declare(strict_types=1);

namespace api\components\User;

use api\exceptions\ThisShouldNotHappenException;
use common\models\Account;
use common\models\AccountSession;
use common\rbac\Roles as R;
use DateInterval;
use DateTime;
use Emarref\Jwt\Algorithm\AlgorithmInterface;
use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Algorithm\Rs256;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Asymmetric as AsymmetricEncryption;
use Emarref\Jwt\Encryption\EncryptionInterface;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Exception\VerificationException;
use Emarref\Jwt\HeaderParameter\Custom;
use Emarref\Jwt\Token;
use Emarref\Jwt\Verification\Context as VerificationContext;
use Exception;
use InvalidArgumentException;
use Webmozart\Assert\Assert;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\UnauthorizedHttpException;
use yii\web\User as YiiUserComponent;

/**
 * @property AccountSession|null $activeSession
 * @property IdentityInterface|null $identity
 *
 * @method IdentityInterface|null loginByAccessToken($token, $type = null)
 * @method IdentityInterface|null getIdentity($autoRenew = true)
 */
class Component extends YiiUserComponent {

    public const KEEP_MINECRAFT_SESSIONS = 1;
    public const KEEP_SITE_SESSIONS = 2;
    public const KEEP_CURRENT_SESSION = 4;

    public const JWT_SUBJECT_PREFIX = 'ely|';

    private const LATEST_JWT_VERSION = 1;

    public $enableSession = false;

    public $loginUrl = null;

    public $identityClass = Identity::class;

    public $secret;

    public $publicKeyPath;

    public $privateKeyPath;

    public $expirationTimeout = 'PT1H';

    public $sessionTimeout = 'P7D';

    private $publicKey;

    private $privateKey;

    /**
     * @var Token[]
     */
    private static $parsedTokensCache = [];

    public function init() {
        parent::init();
        Assert::notEmpty($this->secret, 'secret must be specified');
        Assert::notEmpty($this->publicKeyPath, 'public key path must be specified');
        Assert::notEmpty($this->privateKeyPath, 'private key path must be specified');
    }

    public function findIdentityByAccessToken($accessToken): ?IdentityInterface {
        if (empty($accessToken)) {
            return null;
        }

        /** @var \api\components\User\IdentityInterface|string $identityClass */
        $identityClass = $this->identityClass;
        try {
            return $identityClass::findIdentityByAccessToken($accessToken);
        } catch (UnauthorizedHttpException $e) {
            // Do nothing. It's okay to catch this.
        } catch (Exception $e) {
            Yii::error($e);
        }

        return null;
    }

    public function createJwtAuthenticationToken(Account $account, AccountSession $session = null): Token {
        $token = $this->createToken($account);
        if ($session !== null) {
            $token->addClaim(new Claim\JwtId($session->id));
        } else {
            // If we don't remember a session, the token should live longer
            // so that the session doesn't end while working with the account
            $token->addClaim(new Claim\Expiration((new DateTime())->add(new DateInterval($this->sessionTimeout))));
        }

        return $token;
    }

    public function renewJwtAuthenticationToken(AccountSession $session): AuthenticationResult {
        $transaction = Yii::$app->db->beginTransaction();

        $account = $session->account;
        $token = $this->createToken($account);
        $token->addClaim(new Claim\JwtId($session->id));
        $jwt = $this->serializeToken($token);

        $result = new AuthenticationResult($account, $jwt, $session);

        $session->setIp(Yii::$app->request->userIP);
        $session->last_refreshed_at = time();
        if (!$session->save()) {
            throw new ThisShouldNotHappenException('Cannot update session info');
        }

        $transaction->commit();

        return $result;
    }

    public function serializeToken(Token $token): string {
        $encryption = $this->getEncryptionForVersion(self::LATEST_JWT_VERSION);
        $this->prepareEncryptionForEncoding($encryption);

        return (new Jwt())->serialize($token, $encryption);
    }

    /**
     * @param string $jwtString
     * @return Token
     * @throws VerificationException in case when some Claim not pass the validation
     */
    public function parseToken(string $jwtString): Token {
        $token = &self::$parsedTokensCache[$jwtString];
        if ($token === null) {
            $jwt = new Jwt();
            try {
                $notVerifiedToken = $jwt->deserialize($jwtString);
            } catch (Exception $e) {
                throw new VerificationException('Incorrect token encoding', 0, $e);
            }

            $versionHeader = $notVerifiedToken->getHeader()->findParameterByName('v');
            $version = $versionHeader ? $versionHeader->getValue() : 0;
            $encryption = $this->getEncryptionForVersion($version);
            $this->prepareEncryptionForDecoding($encryption);

            $context = new VerificationContext($encryption);
            $context->setSubject(self::JWT_SUBJECT_PREFIX);
            $jwt->verify($notVerifiedToken, $context);

            $token = $notVerifiedToken;
        }

        return $token;
    }

    /**
     * The method searches AccountSession model, which one has been used to create current JWT token.
     * null will be returned in case when any of the following situations occurred:
     * - The user isn't authorized
     * - There is no header with a token
     * - Token validation isn't passed and some exception has been thrown
     * - No session key found in the token. This is possible if the user chose not to remember me
     *   or just some old tokens, without the support of saving the used session
     *
     * @return AccountSession|null
     */
    public function getActiveSession(): ?AccountSession {
        if ($this->getIsGuest()) {
            return null;
        }

        $bearer = $this->getBearerToken();
        if ($bearer === null) {
            return null;
        }

        try {
            $token = $this->parseToken($bearer);
        } catch (VerificationException $e) {
            return null;
        }

        $sessionId = $token->getPayload()->findClaimByName(Claim\JwtId::NAME);
        if ($sessionId === null) {
            return null;
        }

        return AccountSession::findOne($sessionId->getValue());
    }

    public function terminateSessions(Account $account, int $mode = 0): void {
        $currentSession = null;
        if ($mode & self::KEEP_CURRENT_SESSION) {
            $currentSession = $this->getActiveSession();
        }

        if (!($mode & self::KEEP_SITE_SESSIONS)) {
            foreach ($account->sessions as $session) {
                if ($currentSession === null || $currentSession->id !== $session->id) {
                    $session->delete();
                }
            }
        }

        if (!($mode & self::KEEP_MINECRAFT_SESSIONS)) {
            foreach ($account->minecraftAccessKeys as $minecraftAccessKey) {
                $minecraftAccessKey->delete();
            }
        }
    }

    private function getPublicKey() {
        if (empty($this->publicKey)) {
            if (!($this->publicKey = file_get_contents($this->publicKeyPath))) {
                throw new InvalidConfigException('invalid public key path');
            }
        }

        return $this->publicKey;
    }

    private function getPrivateKey() {
        if (empty($this->privateKey)) {
            if (!($this->privateKey = file_get_contents($this->privateKeyPath))) {
                throw new InvalidConfigException('invalid private key path');
            }
        }

        return $this->privateKey;
    }

    private function createToken(Account $account): Token {
        $token = new Token();
        $token->addHeader(new Custom('v', 1));
        foreach ($this->getClaims($account) as $claim) {
            $token->addClaim($claim);
        }

        return $token;
    }

    /**
     * @param Account $account
     * @return Claim\AbstractClaim[]
     */
    private function getClaims(Account $account): array {
        $currentTime = new DateTime();

        return [
            new ScopesClaim([R::ACCOUNTS_WEB_USER]),
            new Claim\IssuedAt($currentTime),
            new Claim\Expiration($currentTime->add(new DateInterval($this->expirationTimeout))),
            new Claim\Subject(self::JWT_SUBJECT_PREFIX . $account->id),
        ];
    }

    private function getEncryptionForVersion(int $version): EncryptionInterface {
        return EncryptionFactory::create($this->getAlgorithm($version ?? 0));
    }

    private function getAlgorithm(int $version): AlgorithmInterface {
        switch ($version) {
            case 0:
                return new Hs256($this->secret);
            case 1:
                return new Rs256();
        }

        throw new InvalidArgumentException('Unsupported token version');
    }

    private function getBearerToken(): ?string {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader === null || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

    private function prepareEncryptionForEncoding(EncryptionInterface $encryption): void {
        if ($encryption instanceof AsymmetricEncryption) {
            $encryption->setPrivateKey($this->getPrivateKey());
        }
    }

    private function prepareEncryptionForDecoding(EncryptionInterface $encryption) {
        if ($encryption instanceof AsymmetricEncryption) {
            $encryption->setPublicKey($this->getPublicKey());
        }
    }

}
