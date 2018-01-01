<?php
namespace api\components\User;

use api\exceptions\ThisShouldNotHappenException;
use common\models\Account;
use common\models\AccountSession;
use common\rbac\Roles as R;
use DateInterval;
use DateTime;
use Emarref\Jwt\Algorithm\AlgorithmInterface;
use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Exception\VerificationException;
use Emarref\Jwt\Token;
use Emarref\Jwt\Verification\Context as VerificationContext;
use Exception;
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

    public $enableSession = false;

    public $loginUrl = null;

    public $identityClass = Identity::class;

    public $secret;

    public $expirationTimeout = 'PT1H';

    public $sessionTimeout = 'P7D';

    /**
     * @var Token[]
     */
    private static $parsedTokensCache = [];

    public function init() {
        parent::init();
        if (!$this->secret) {
            throw new InvalidConfigException('secret must be specified');
        }
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

    public function createJwtAuthenticationToken(Account $account, bool $rememberMe): AuthenticationResult {
        $ip = Yii::$app->request->userIP;
        $token = $this->createToken($account);
        if ($rememberMe) {
            $session = new AccountSession();
            $session->account_id = $account->id;
            $session->setIp($ip);
            $session->generateRefreshToken();
            if (!$session->save()) {
                throw new ThisShouldNotHappenException('Cannot save account session model');
            }

            $token->addClaim(new Claim\JwtId($session->id));
        } else {
            $session = null;
            // Если мы не сохраняем сессию, то токен должен жить подольше,
            // чтобы не прогорала сессия во время работы с аккаунтом
            $token->addClaim(new Claim\Expiration((new DateTime())->add(new DateInterval($this->sessionTimeout))));
        }

        $jwt = $this->serializeToken($token);

        return new AuthenticationResult($account, $jwt, $session);
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

    /**
     * @param string $jwtString
     * @return Token распаршенный токен
     * @throws VerificationException если один из Claims не пройдёт проверку
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

            $context = new VerificationContext(EncryptionFactory::create($this->getAlgorithm()));
            $context->setSubject(self::JWT_SUBJECT_PREFIX);
            $jwt->verify($notVerifiedToken, $context);

            $token = $notVerifiedToken;
        }

        return $token;
    }

    /**
     * Метод находит AccountSession модель, относительно которой был выдан текущий JWT токен.
     * В случае, если на пути поиска встретится ошибка, будет возвращено значение null. Возможные кейсы:
     * - Юзер не авторизован
     * - Почему-то нет заголовка с токеном
     * - Во время проверки токена возникла ошибка, что привело к исключению
     * - В токене не найдено ключа сессии. Такое возможно, если юзер выбрал "не запоминать меня"
     * или просто старые токены, без поддержки сохранения используемой сессии
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

    public function getAlgorithm(): AlgorithmInterface {
        return new Hs256($this->secret);
    }

    protected function serializeToken(Token $token): string {
        return (new Jwt())->serialize($token, EncryptionFactory::create($this->getAlgorithm()));
    }

    protected function createToken(Account $account): Token {
        $token = new Token();
        foreach($this->getClaims($account) as $claim) {
            $token->addClaim($claim);
        }

        return $token;
    }

    /**
     * @param Account $account
     * @return Claim\AbstractClaim[]
     */
    protected function getClaims(Account $account): array {
        $currentTime = new DateTime();

        return [
            new ScopesClaim([R::ACCOUNTS_WEB_USER]),
            new Claim\IssuedAt($currentTime),
            new Claim\Expiration($currentTime->add(new DateInterval($this->expirationTimeout))),
            new Claim\Subject(self::JWT_SUBJECT_PREFIX . $account->id),
        ];
    }

    private function getBearerToken() {
        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader === null || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

}
