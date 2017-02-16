<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\RefreshTokenEntity;
use common\components\Redis\Key;
use common\components\Redis\Set;
use common\models\OauthSession;
use ErrorException;
use League\OAuth2\Server\Entity\RefreshTokenEntity as OriginalRefreshTokenEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\RefreshTokenInterface;
use Yii;
use yii\helpers\Json;

class RefreshTokenStorage extends AbstractStorage implements RefreshTokenInterface {

    public $dataTable = 'oauth_refresh_tokens';

    public function get($token) {
        $result = Json::decode((new Key($this->dataTable, $token))->getValue());
        if ($result === null) {
            return null;
        }

        $entity = new RefreshTokenEntity($this->server);
        $entity->setId($result['id']);
        $entity->setAccessTokenId($result['access_token_id']);
        $entity->setSessionId($result['session_id']);

        return $entity;
    }

    public function create($token, $expireTime, $accessToken) {
        $sessionId = $this->server->getAccessTokenStorage()->get($accessToken)->getSession()->getId();
        $payload = Json::encode([
            'id' => $token,
            'access_token_id' => $accessToken,
            'session_id' => $sessionId,
        ]);

        $this->key($token)->setValue($payload);
        $this->sessionHash($sessionId)->add($token);
    }

    public function delete(OriginalRefreshTokenEntity $token) {
        if (!$token instanceof RefreshTokenEntity) {
            throw new ErrorException('Token must be instance of ' . RefreshTokenEntity::class);
        }

        $this->key($token->getId())->delete();
        $this->sessionHash($token->getSessionId())->remove($token->getId());
    }

    public function sessionHash(string $sessionId) : Set {
        $tableName = Yii::$app->db->getSchema()->getRawTableName(OauthSession::tableName());
        return new Set($tableName, $sessionId, 'refresh_tokens');
    }

    private function key(string $token) : Key {
        return new Key($this->dataTable, $token);
    }

}
