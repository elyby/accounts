<?php
namespace api\components\OAuth2\Entities;

class ScopeEntity extends \League\OAuth2\Server\Entity\ScopeEntity {

    public function setId(string $id) {
        $this->id = $id;
    }

}
