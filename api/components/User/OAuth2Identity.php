<?php
declare(strict_types=1);

namespace api\components\User;

use api\components\OAuth2\Entities\AccessTokenEntity;
use common\models\Account;
use common\models\OauthSession;
use Yii;
use yii\base\NotSupportedException;
use yii\web\UnauthorizedHttpException;

class OAuth2Identity implements IdentityInterface {

    /**
     * @var AccessTokenEntity
     */
    private $_accessToken;

    private function __construct(AccessTokenEntity $accessToken) {
        $this->_accessToken = $accessToken;
    }

    /**
     * @inheritdoc
     * @throws UnauthorizedHttpException
     * @return IdentityInterface
     */
    public static function findIdentityByAccessToken($token, $type = null): IdentityInterface {
        /** @var AccessTokenEntity|null $model */
        // TODO: rework
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

    // @codeCoverageIgnoreStart
    public function getAuthKey() {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public function validateAuthKey($authKey) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    public static function findIdentity($id) {
        throw new NotSupportedException('This method used for cookie auth, except we using Bearer auth');
    }

    // @codeCoverageIgnoreEnd

    private function getSession(): OauthSession {
        return OauthSession::findOne(['id' => $this->_accessToken->getSessionId()]);
    }

}
