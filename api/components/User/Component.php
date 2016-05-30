<?php
namespace api\components\User;

use api\models\AccountIdentity;
use common\models\AccountSession;
use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\web\IdentityInterface;
use yii\web\User as YiiUserComponent;

class Component extends YiiUserComponent {

    public $secret;

    public $expirationTimeout = 3600; // 1h

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

        $jwt = $this->getJWT($identity);
        if ($rememberMe) {
            $session = new AccountSession();
            $session->account_id = $id;
            $session->setIp($ip);
            $session->generateRefreshToken();
            if (!$session->save()) {
                throw new ErrorException('Cannot save account session model');
            }
        } else {
            $session = null;
        }

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
            $jwt = $this->getJWT($identity);

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

    public function getJWT(IdentityInterface $identity) {
        $jwt = new Jwt();
        $token = new Token();
        foreach($this->getClaims($identity) as $claim) {
            $token->addClaim($claim);
        }

        return $jwt->serialize($token, EncryptionFactory::create($this->getAlgorithm()));
    }

    /**
     * @return Hs256
     */
    public function getAlgorithm() {
        return new Hs256($this->secret);
    }

    /**
     * @param IdentityInterface $identity
     *
     * @return Claim\AbstractClaim[]
     */
    protected function getClaims(IdentityInterface $identity) {
        $currentTime = time();
        $hostInfo = Yii::$app->request->hostInfo;

        return [
            new Claim\Audience($hostInfo),
            new Claim\Issuer($hostInfo),
            new Claim\IssuedAt($currentTime),
            new Claim\Expiration($currentTime + $this->expirationTimeout),
            new Claim\JwtId($identity->getId()),
        ];
    }

}
