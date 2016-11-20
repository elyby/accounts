<?php
namespace api\components\User;

use api\models\AccountIdentity;
use common\models\AccountSession;
use DateInterval;
use DateTime;
use Emarref\Jwt\Algorithm\AlgorithmInterface;
use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Exception\VerificationException;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Emarref\Jwt\Verification\Context as VerificationContext;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\web\IdentityInterface;
use yii\web\User as YiiUserComponent;

/**
 * @property AccountSession|null $activeSession
 * @property AccountIdentity|null $identity
 *
 * @method AccountIdentity|null getIdentity($autoRenew = true)
 */
class Component extends YiiUserComponent {

    public $enableSession = false;

    public $loginUrl = null;

    public $identityClass = AccountIdentity::class;

    public $secret;

    public $expirationTimeout = 'PT1H';

    public $sessionTimeout = 'P7D';

    public function init() {
        parent::init();
        if (!$this->secret) {
            throw new InvalidConfigException('secret must be specified');
        }
    }

    /**
     * @param IdentityInterface $identity
     * @param bool              $rememberMe
     *
     * @return LoginResult|bool
     * @throws ErrorException
     */
    public function login(IdentityInterface $identity, $rememberMe = false) {
        if (!$this->beforeLogin($identity, false, $rememberMe)) {
            return false;
        }

        $this->switchIdentity($identity, 0);

        $id = $identity->getId();
        $ip = Yii::$app->request->userIP;
        $token = $this->createToken($identity);
        if ($rememberMe) {
            $session = new AccountSession();
            $session->account_id = $id;
            $session->setIp($ip);
            $session->generateRefreshToken();
            if (!$session->save()) {
                throw new ErrorException('Cannot save account session model');
            }

            $token->addClaim(new SessionIdClaim($session->id));
        } else {
            $session = null;
            // Если мы не сохраняем сессию, то токен должен жить подольше, чтобы
            // не прогорала сессия во время работы с аккаунтом
            $token->addClaim(new Claim\Expiration((new DateTime())->add(new DateInterval($this->sessionTimeout))));
        }

        $jwt = $this->serializeToken($token);

        Yii::info("User '{$id}' logged in from {$ip}.", __METHOD__);

        $result = new LoginResult($identity, $jwt, $session);
        $this->afterLogin($identity, false, $rememberMe);

        return $result;
    }

    public function renew(AccountSession $session) {
        $account = $session->account;
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $identity = new AccountIdentity($account->attributes);
            $token = $this->createToken($identity);
            $jwt = $this->serializeToken($token);

            $result = new RenewResult($identity, $jwt);

            $session->setIp(Yii::$app->request->userIP);
            $session->last_refreshed_at = time();
            if (!$session->save()) {
                throw new ErrorException('Cannot update session info');
            }

            $transaction->commit();
        } catch (ErrorException $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $result;
    }

    /**
     * @param string $jwtString
     * @return Token распаршенный токен
     * @throws VerificationException если один из Claims не пройдёт проверку
     */
    public function parseToken(string $jwtString) : Token {
        $hostInfo = Yii::$app->request->hostInfo;

        $jwt = new Jwt();
        $token = $jwt->deserialize($jwtString);
        $context = new VerificationContext(EncryptionFactory::create($this->getAlgorithm()));
        $context->setAudience($hostInfo);
        $context->setIssuer($hostInfo);
        $jwt->verify($token, $context);

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
    public function getActiveSession() {
        if ($this->getIsGuest()) {
            return null;
        }

        $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
        if ($authHeader === null || !preg_match('/^Bearer\s+(.*?)$/', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        try {
            $token = $this->parseToken($token);
        } catch (VerificationException $e) {
            return null;
        }

        $sessionId = $token->getPayload()->findClaimByName(SessionIdClaim::NAME);
        if ($sessionId === null) {
            return null;
        }

        return AccountSession::findOne($sessionId->getValue());
    }

    public function getAlgorithm() : AlgorithmInterface {
        return new Hs256($this->secret);
    }

    protected function serializeToken(Token $token) : string {
        return (new Jwt())->serialize($token, EncryptionFactory::create($this->getAlgorithm()));
    }

    protected function createToken(IdentityInterface $identity) : Token {
        $token = new Token();
        foreach($this->getClaims($identity) as $claim) {
            $token->addClaim($claim);
        }

        return $token;
    }

    /**
     * @param IdentityInterface $identity
     * @return Claim\AbstractClaim[]
     */
    protected function getClaims(IdentityInterface $identity) {
        $currentTime = new DateTime();
        $hostInfo = Yii::$app->request->hostInfo;

        return [
            new Claim\Audience($hostInfo),
            new Claim\Issuer($hostInfo),
            new Claim\IssuedAt($currentTime),
            new Claim\Expiration($currentTime->add(new DateInterval($this->expirationTimeout))),
            new Claim\JwtId($identity->getId()),
        ];
    }

}
