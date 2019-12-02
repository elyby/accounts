<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use api\components\OAuth2\Storage\SessionStorage;
use League\OAuth2\Server\Entity\SessionEntity as OriginalSessionEntity;
use Webmozart\Assert\Assert;

class RefreshTokenEntity extends \League\OAuth2\Server\Entity\RefreshTokenEntity {

    private $sessionId;

    public function isExpired(): bool {
        return false;
    }

    public function getSession(): ?SessionEntity {
        if ($this->session instanceof SessionEntity) {
            return $this->session;
        }

        /** @var SessionStorage $sessionStorage */
        $sessionStorage = $this->server->getSessionStorage();
        Assert::isInstanceOf($sessionStorage, SessionStorage::class);

        return $sessionStorage->getById($this->sessionId);
    }

    public function getSessionId(): int {
        return $this->sessionId;
    }

    public function setSession(OriginalSessionEntity $session): self {
        parent::setSession($session);
        $this->setSessionId((int)$session->getId());

        return $this;
    }

    public function setSessionId(int $sessionId): void {
        $this->sessionId = $sessionId;
    }

}
