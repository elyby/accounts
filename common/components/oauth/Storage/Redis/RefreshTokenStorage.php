<?php
namespace common\components\oauth\Storage\Redis;

use common\components\redis\Key;
use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Storage\AbstractStorage;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

class RefreshTokenStorage extends AbstractStorage implements RefreshTokenInterface {

    public $dataTable = 'oauth_refresh_tokens';

    /**
     * @inheritdoc
     */
    public function get($token) {
        $result = (new Key($this->dataTable, $token))->getValue();
        if (!$result) {
            return null;
        }

        return (new RefreshTokenEntity($this->server))
            ->setId($result['id'])
            ->setExpireTime($result['expire_time'])
            ->setAccessTokenId($result['access_token_id']);
    }

    /**
     * @inheritdoc
     */
    public function create($token, $expireTime, $accessToken) {
        $payload = [
            'id' => $token,
            'expire_time' => $expireTime,
            'access_token_id' => $accessToken,
        ];

        (new Key($this->dataTable, $token))->setValue($payload);
    }

    /**
     * @inheritdoc
     */
    public function delete(RefreshTokenEntity $token) {
        (new Key($this->dataTable, $token->getId()))->delete();
    }

}
