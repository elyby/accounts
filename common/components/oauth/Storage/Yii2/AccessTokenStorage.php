<?php
namespace common\components\oauth\Storage\Yii2;

use common\components\oauth\Entity\AccessTokenEntity;
use common\models\OauthAccessToken;
use League\OAuth2\Server\Entity\AccessTokenEntity as OriginalAccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use yii\db\Exception;

class AccessTokenStorage extends AbstractStorage implements AccessTokenInterface {

    private $cache = [];

    /**
     * @param string $token
     * @return OauthAccessToken|null
     */
    private function getTokenModel($token) {
        if (!isset($this->cache[$token])) {
            $this->cache[$token] = OauthAccessToken::findOne($token);
        }

        return $this->cache[$token];
    }

    /**
     * @inheritdoc
     */
    public function get($token) {
        $model = $this->getTokenModel($token);
        if ($model === null) {
            return null;
        }

        return (new AccessTokenEntity($this->server))->hydrate([
            'id' => $model->access_token,
            'expireTime' => $model->expire_time,
            'sessionId' => $model->session_id,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getScopes(OriginalAccessTokenEntity $token) {
        $entities = [];
        foreach($this->getTokenModel($token->getId())->getScopes() as $scope) {
            $entities[] = (new ScopeEntity($this->server))->hydrate(['id' => $scope]);
        }

        return $entities;
    }

    /**
     * @inheritdoc
     */
    public function create($token, $expireTime, $sessionId) {
        $model = new OauthAccessToken();
        $model->access_token = $token;
        $model->expire_time = $expireTime;
        $model->session_id = $sessionId;

        if (!$model->save()) {
            throw new Exception('Cannot save ' . OauthAccessToken::class . ' model.');
        }
    }

    /**
     * @inheritdoc
     */
    public function associateScope(OriginalAccessTokenEntity $token, ScopeEntity $scope) {
        $this->getTokenModel($token->getId())->getScopes()->add($scope->getId());
    }

    /**
     * @inheritdoc
     */
    public function delete(OriginalAccessTokenEntity $token) {
        $this->getTokenModel($token->getId())->delete();
    }

}
