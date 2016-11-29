<?php
namespace api\components\OAuth2\Entities;

use api\components\OAuth2\Storage\SessionStorage;
use ErrorException;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;

class AccessTokenEntity extends \League\OAuth2\Server\Entity\AccessTokenEntity {

    protected $sessionId;

    public function getSessionId() {
        return $this->sessionId;
    }

    public function setSessionId($sessionId) {
        $this->sessionId = $sessionId;
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

    public function getSession() {
        if ($this->session instanceof OriginalSessionEntity) {
            return $this->session;
        }

        $sessionStorage = $this->server->getSessionStorage();
        if (!$sessionStorage instanceof SessionStorage) {
            throw new ErrorException('SessionStorage must be instance of ' . SessionStorage::class);
        }

        return $sessionStorage->getById($this->sessionId);
    }

}
