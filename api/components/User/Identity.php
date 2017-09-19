<?php
namespace api\components\User;

use api\components\OAuth2\Entities\AccessTokenEntity;
use common\models\Account;
use common\models\OauthSession;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

/**
 * @property Account $account
 */
class Identity implements IdentityInterface {

    /**
     * @var AccessTokenEntity
     */
    private $_accessToken;

    /**
     * @inheritdoc
     * @throws \yii\web\UnauthorizedHttpException
     * @return IdentityInterface
     */
    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        if ($token === null) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        // Speed-improved analogue of the `count(explode('.', $token)) === 3`
        if (substr_count($token, '.') === 2) {
            return JwtIdentity::findIdentityByAccessToken($token, $type);
        }

        /** @var AccessTokenEntity|null $model */
        $model = Yii::$app->oauth->getAccessTokenStorage()->get($token);
        if ($model === null) {
            throw new UnauthorizedHttpException('Incorrect token');
        }

        if ($model->isExpired()) {
            throw new UnauthorizedHttpException('Token expired');
        }

        return new static($model);
    }

    public function getAccount(): ?Account {
        return $this->getSession()->account;
    }

    /**
     * @return string[]
     */
    public function getAssignedPermissions(): array {
        return array_keys($this->_accessToken->getScopes());
    }

    public function getId(): string {
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

    private function __construct(AccessTokenEntity $accessToken) {
        $this->_accessToken = $accessToken;
    }

    private function getSession(): OauthSession {
        return OauthSession::findOne($this->_accessToken->getSessionId());
    }

}
