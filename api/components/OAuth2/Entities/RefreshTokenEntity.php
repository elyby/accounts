<?php
namespace api\components\OAuth2\Entities;

class RefreshTokenEntity extends \League\OAuth2\Server\Entity\RefreshTokenEntity {

    public function isExpired() : bool {
        return false;
    }

}
