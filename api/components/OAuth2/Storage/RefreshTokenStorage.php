<?php
namespace api\components\OAuth2\Storage;

use api\components\OAuth2\Entities\RefreshTokenEntity;
use common\components\Redis\Key;
use League\OAuth2\Server\Entity\RefreshTokenEntity as OriginalRefreshTokenEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

class RefreshTokenStorage extends AbstractStorage implements RefreshTokenInterface {

    public $dataTable = 'oauth_refresh_tokens';

    /**
     * @inheritdoc
     */
    public function get($token) {
        $result = json_decode((new Key($this->dataTable, $token))->getValue(), true);
        if (!$result) {
            return null;
        }

        $entity = new RefreshTokenEntity($this->server);
        $entity->setId($result['id']);
        $entity->setAccessTokenId($result['access_token_id']);

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function create($token, $expireTime, $accessToken) {
        $payload = [
            'id' => $token,
            'access_token_id' => $accessToken,
        ];

        (new Key($this->dataTable, $token))->setValue($payload);
    }

    /**
     * @inheritdoc
     */
    public function delete(OriginalRefreshTokenEntity $token) {
        (new Key($this->dataTable, $token->getId()))->delete();
    }

}
