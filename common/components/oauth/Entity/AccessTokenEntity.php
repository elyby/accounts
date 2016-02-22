<?php
namespace common\components\oauth\Entity;

use League\OAuth2\Server\Entity\EntityTrait;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;

class AccessTokenEntity extends \League\OAuth2\Server\Entity\AccessTokenEntity {
    use EntityTrait;

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
