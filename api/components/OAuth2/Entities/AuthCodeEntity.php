<?php
namespace api\components\OAuth2\Entities;

use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;

class AuthCodeEntity extends \League\OAuth2\Server\Entity\AuthCodeEntity {

    protected $sessionId;

    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * @inheritdoc
     * @return static
     */
    public function setSession(OriginalSessionEntity $session) {
        parent::setSession($session);
        $this->sessionId = $session->getId();

        return $this;
    }

}
