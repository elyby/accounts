<?php
namespace common\components\oauth\Entity;

use League\OAuth2\Server\Entity\EntityTrait;
use League\OAuth2\Server\Entity\SessionEntity;

class AuthCodeEntity extends \League\OAuth2\Server\Entity\AuthCodeEntity {
    use EntityTrait;

    protected $sessionId;

    public function getSessionId() {
        return $this->sessionId;
    }

    /**
     * @inheritdoc
     * @return static
     */
    public function setSession(SessionEntity $session) {
        parent::setSession($session);
        $this->sessionId = $session->getId();

        return $this;
    }

}
