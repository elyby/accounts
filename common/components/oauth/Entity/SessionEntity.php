<?php
namespace common\components\oauth\Entity;

use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\EntityTrait;

class SessionEntity extends \League\OAuth2\Server\Entity\SessionEntity {
    use EntityTrait;

    protected $clientId;

    public function getClientId() {
        return $this->clientId;
    }

    /**
     * @inheritdoc
     * @return static
     */
    public function associateClient(ClientEntity $client) {
        parent::associateClient($client);
        $this->clientId = $client->getId();

        return $this;
    }

}
