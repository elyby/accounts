<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities;

class AuthCodeGrant extends \League\OAuth2\Server\Grant\AuthCodeGrant {

    protected function createAccessTokenEntity() {
        return new Entities\AccessTokenEntity($this->server);
    }

    protected function createRefreshTokenEntity() {
        return new Entities\RefreshTokenEntity($this->server);
    }

    protected function createSessionEntity() {
        return new Entities\SessionEntity($this->server);
    }

}
