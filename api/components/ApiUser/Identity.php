<?php
namespace api\components\ApiUser;

use api\components\OAuth2\Entities\AccessTokenEntity;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use Yii;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use yii\web\UnauthorizedHttpException;

/**
 * @property Account           $account
 * @property OauthClient       $client
 * @property OauthSession      $session
 * @property AccessTokenEntity $accessToken
 */
class Identity implements IdentityInterface {

    /**
     * @var AccessTokenEntity
     */
    private $_accessToken;

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        $model = Yii::$app->oauth->getAuthServer()->getAccessTokenStorage()->get($token);
        if ($model === null) {
            throw new UnauthorizedHttpException('Incorrect token');
        } elseif ($model->isExpired()) {
            throw new UnauthorizedHttpException('Token expired');
        }

        return new static($model);
    }

    private function __construct(AccessTokenEntity $accessToken) {
        $this->_accessToken = $accessToken;
    }

    public function getAccount() : Account {
        return $this->getSession()->account;
    }

    public function getClient() : OauthClient {
        return $this->getSession()->client;
    }

    public function getSession() : OauthSession {
        return OauthSession::findOne($this->_accessToken->getSessionId());
    }

    public function getAccessToken() : AccessTokenEntity {
        return $this->_accessToken;
    }

    /**
     * Этот метод используется для получения токена, к которому привязаны права.
     * У нас права привязываются к токенам, так что возвращаем именно его id.
     * @inheritdoc
     */
    public function getId() {
        return $this->_accessToken->getId();
    }

    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public static function findIdentity($id) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

}
