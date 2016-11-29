<?php
namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\Entity\ClientEntity as OriginalClientEntity;
use League\OAuth2\Server\Entity\EntityTrait;

class SessionEntity extends \League\OAuth2\Server\Entity\SessionEntity {
    use EntityTrait;

    protected $clientId;

    public function getClientId() {
        return $this->clientId;
    }

    public function associateClient(OriginalClientEntity $client) {
        parent::associateClient($client);
        $this->clientId = $client->getId();

        return $this;
    }

    public function setClientId(string $clientId) {
        $this->clientId = $clientId;
    }

}
