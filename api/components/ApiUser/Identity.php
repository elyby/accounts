<?php
namespace api\components\ApiUser;

use common\models\Account;
use common\models\OauthAccessToken;
use common\models\OauthClient;
use common\models\OauthSession;
use yii\base\NotSupportedException;
use yii\web\IdentityInterface;
use yii\web\UnauthorizedHttpException;

/**
 * @property Account          $account
 * @property OauthClient      $client
 * @property OauthSession     $session
 * @property OauthAccessToken $accessToken
 */
class Identity implements IdentityInterface {

    /**
     * @var OauthAccessToken
     */
    private $_accessToken;

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null) {
        /** @var OauthAccessToken|null $model */
        $model = OauthAccessToken::findOne($token);
        if ($model === null) {
            throw new UnauthorizedHttpException('Incorrect token');
        } elseif ($model->isExpired()) {
            throw new UnauthorizedHttpException('Token expired');
        }

        return new static($model);
    }

    private function __construct(OauthAccessToken $accessToken) {
        $this->_accessToken = $accessToken;
    }

    public function getAccount() : Account {
        return $this->getSession()->account;
    }

    public function getClient() : OauthClient {
        return $this->getSession()->client;
    }

    public function getSession() : OauthSession {
        return $this->_accessToken->session;
    }

    public function getAccessToken() : OauthAccessToken {
        return $this->_accessToken;
    }

    /**
     * Этот метод используется для получения пользователя, к которому привязаны права.
     * У нас права привязываются к токенам, так что возвращаем именно его id.
     * @inheritdoc
     */
    public function getId() {
        return $this->_accessToken->access_token;
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
