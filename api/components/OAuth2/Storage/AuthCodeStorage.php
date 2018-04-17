<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\AuthCodeEntity;
use common\components\Redis\Key;
use common\components\Redis\Set;
use League\OAuth2\Server\Entity\AuthCodeEntity as OriginalAuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\AuthCodeInterface;
use yii\helpers\Json;

class AuthCodeStorage extends AbstractStorage implements AuthCodeInterface {

    public $dataTable = 'oauth_auth_codes';

    public function get($code) {
        $result = Json::decode((new Key($this->dataTable, $code))->getValue());
        if ($result === null) {
            return null;
        }

        $entity = new AuthCodeEntity($this->server);
        $entity->setId($result['id']);
        $entity->setExpireTime($result['expire_time']);
        $entity->setSessionId($result['session_id']);
        $entity->setRedirectUri($result['client_redirect_uri']);

        return $entity;
    }

    public function create($token, $expireTime, $sessionId, $redirectUri) {
        $payload = Json::encode([
            'id' => $token,
            'expire_time' => $expireTime,
            'session_id' => $sessionId,
            'client_redirect_uri' => $redirectUri,
        ]);

        $this->key($token)->setValue($payload)->expireAt($expireTime);
    }

    public function getScopes(OriginalAuthCodeEntity $token) {
        $scopes = $this->scopes($token->getId());
        $scopesEntities = [];
        foreach ($scopes as $scope) {
            if ($this->server->getScopeStorage()->get($scope) !== null) {
                $scopesEntities[] = (new ScopeEntity($this->server))->hydrate(['id' => $scope]);
            }
        }

        return $scopesEntities;
    }

    public function associateScope(OriginalAuthCodeEntity $token, ScopeEntity $scope) {
        $this->scopes($token->getId())->add($scope->getId())->expireAt($token->getExpireTime());
    }

    public function delete(OriginalAuthCodeEntity $token) {
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
