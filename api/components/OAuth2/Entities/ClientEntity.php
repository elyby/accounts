<?php
namespace api\components\OAuth2\Entities;

class ClientEntity extends \League\OAuth2\Server\Entity\ClientEntity {

    public function setId(string $id) {
        $this->id = $id;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function setSecret(string $secret) {
        $this->secret = $secret;
    }

    public function setRedirectUri($redirectUri) {
        $this->redirectUri = $redirectUri;
    }

}
