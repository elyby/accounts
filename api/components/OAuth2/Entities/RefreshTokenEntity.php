<?php
namespace api\components\OAuth2\Entities;

use api\components\OAuth2\Storage\SessionStorage;
use ErrorException;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;

class RefreshTokenEntity extends \League\OAuth2\Server\Entity\RefreshTokenEntity {

    private $sessionId;

    public function isExpired() : bool {
        return false;
    }

    public function getSession() : SessionEntity {
        if ($this->session instanceof SessionEntity) {
            return $this->session;
        }

        $sessionStorage = $this->server->getSessionStorage();
        if (!$sessionStorage instanceof SessionStorage) {
            throw new ErrorException('SessionStorage must be instance of ' . SessionStorage::class);
        }

        return $sessionStorage->getById($this->sessionId);
    }

    public function getSessionId() : int {
        return $this->sessionId;
    }

    public function setSession(OriginalSessionEntity $session) {
        parent::setSession($session);
        $this->setSessionId($session->getId());

        return $this;
    }

    public function setSessionId(int $sessionId) {
        $this->sessionId = $sessionId;
    }

}
