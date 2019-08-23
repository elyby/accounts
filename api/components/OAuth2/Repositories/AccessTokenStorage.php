<?php
namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\AccessTokenEntity;
use common\components\Redis\Key;
use common\components\Redis\Set;
use League\OAuth2\Server\Entity\AccessTokenEntity as OriginalAccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use yii\helpers\Json;

class AccessTokenStorage extends AbstractStorage implements AccessTokenInterface {

    public $dataTable = 'oauth_access_tokens';

    public function get($token) {
        $result = Json::decode((new Key($this->dataTable, $token))->getValue());
        if ($result === null) {
            return null;
        }

        $token = new AccessTokenEntity($this->server);
        $token->setId($result['id']);
        $token->setExpireTime($result['expire_time']);
        $token->setSessionId($result['session_id']);

        return $token;
    }

    public function getScopes(OriginalAccessTokenEntity $token) {
        $scopes = $this->scopes($token->getId());
        $entities = [];
        foreach ($scopes as $scope) {
            if ($this->server->getScopeStorage()->get($scope) !== null) {
                $entities[] = (new ScopeEntity($this->server))->hydrate(['id' => $scope]);
            }
        }

        return $entities;
    }

    public function create($token, $expireTime, $sessionId) {
        $payload = Json::encode([
            'id' => $token,
            'expire_time' => $expireTime,
            'session_id' => $sessionId,
        ]);

        $this->key($token)->setValue($payload)->expireAt($expireTime);
    }

    public function associateScope(OriginalAccessTokenEntity $token, ScopeEntity $scope) {
        $this->scopes($token->getId())->add($scope->getId())->expireAt($token->getExpireTime());
    }

    public function delete(OriginalAccessTokenEntity $token) {
        $this->key($token->getId())->delete();
        $this->scopes($token->getId())->delete();
    }

    private function key(string $token): Key {
        return new Key($this->dataTable, $token);
    }

    private function scopes(string $token): Set {
        return new Set($this->dataTable, $token, 'scopes');
    }

}
