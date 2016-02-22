<?php
namespace common\components\oauth\Storage\Redis;

use common\components\oauth\Entity\AuthCodeEntity;
use common\components\redis\Key;
use common\components\redis\Set;
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
        $result = (new Key($this->dataTable, $code))->getValue();
        if (!$result) {
            return null;
        }

        if ($result['expire_time'] < time()) {
            return null;
        }

        return (new AuthCodeEntity($this->server))->hydrate([
            'id' => $result['id'],
            'redirectUri' => $result['client_redirect_uri'],
            'expireTime' => $result['expire_time'],
            'sessionId' => $result['session_id'],
        ]);
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
        $result = (new Set($this->dataTable, $token->getId(), 'scopes'));
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
