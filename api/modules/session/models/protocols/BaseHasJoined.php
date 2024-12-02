<?php
namespace api\modules\session\models\protocols;

abstract class BaseHasJoined implements HasJoinedInterface {

    private readonly string $username;

    private readonly string $serverId;

    public function __construct(string $username, string $serverId) {
        $this->username = trim($username);
        $this->serverId = trim($serverId);
    }

    public function getUsername(): string {
        return $this->username;
    }

    public function getServerId(): string {
        return $this->serverId;
    }

    public function validate(): bool {
        return $this->username !== '' && $this->serverId !== '';
    }

}
