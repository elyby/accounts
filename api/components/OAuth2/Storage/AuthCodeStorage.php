<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\AuthCodeEntity;
use common\components\Redis\Key;
use common\components\Redis\Set;
use League\OAuth2\Server\Entity\AuthCodeEntity as OriginalAuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\AuthCodeInterface;

class AuthCodeStorage extends AbstractStorage implements AuthCodeInterface {

    public $dataTable = 'oauth_auth_codes';

    public $ttl = 3600; // 1h

    /**
     * @inheritdoc
     */
    public function get($code) {
        $result = json_decode((new Key($this->dataTable, $code))->getValue(), true);
        if (!$result) {
            return null;
        }

        if ($result['expire_time'] < time()) {
            return null;
        }

        /** @var SessionStorage $sessionStorage */
        $sessionStorage = $this->server->getSessionStorage();

        $entity = new AuthCodeEntity($this->server);
        $entity->setId($result['id']);
        $entity->setRedirectUri($result['client_redirect_uri']);
        $entity->setExpireTime($result['expire_time']);
        $entity->setSession($sessionStorage->getById($result['session_id']));

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function create($token, $expireTime, $sessionId, $redirectUri) {
        $payload = [
            'id' => $token,
            'expire_time' => $expireTime,
            'session_id' => $sessionId,
            'client_redirect_uri' => $redirectUri,
        ];

        (new Key($this->dataTable, $token))->setValue($payload)->expire($this->ttl);
    }

    /**
     * @inheritdoc
     */
    public function getScopes(OriginalAuthCodeEntity $token) {
        $result = new Set($this->dataTable, $token->getId(), 'scopes');
        $response = [];
        foreach ($result as $scope) {
            // TODO: нужно проверить все выданные скоупы на их существование
            $response[] = (new ScopeEntity($this->server))->hydrate(['id' => $scope]);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function associateScope(OriginalAuthCodeEntity $token, ScopeEntity $scope) {
        (new Set($this->dataTable, $token->getId(), 'scopes'))->add($scope->getId())->expire($this->ttl);
    }

    /**
     * @inheritdoc
     */
    public function delete(OriginalAuthCodeEntity $token) {
        // Удаляем ключ
        (new Set($this->dataTable, $token->getId()))->delete();
        // Удаляем список скоупов для ключа
        (new Set($this->dataTable, $token->getId(), 'scopes'))->delete();
    }

}
