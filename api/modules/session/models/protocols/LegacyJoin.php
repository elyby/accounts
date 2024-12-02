<?php
namespace api\modules\session\models\protocols;

class LegacyJoin extends BaseJoin {

    private readonly string $user;

    private string $sessionId;

    private readonly string $serverId;

    private $accessToken;

    private ?string $uuid = null;

    public function __construct(string $user, string $sessionId, string $serverId) {
        $this->user = trim($user);
        $this->sessionId = trim($sessionId);
        $this->serverId = trim($serverId);

        $this->parseSessionId($this->sessionId);
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function getSelectedProfile(): string {
        return $this->uuid ?: $this->user;
    }

    public function getServerId(): string {
        return $this->serverId;
    }

    /**
     * @return bool
     */
    public function validate(): bool {
        return !$this->isEmpty($this->accessToken) && !$this->isEmpty($this->user) && !$this->isEmpty($this->serverId);
    }

    /**
     * The method initializes field values to meet the general naming conventions in the project
     *
     * Split by ':' to take into account authorization in modern launchers and login to an legacy version of the game.
     * The sessionId is passed on as "token:{accessToken}:{uuid}", so it needs to be processed
     */
    private function parseSessionId(string $sessionId): void {
        $parts = explode(':', $sessionId);
        if (count($parts) === 3) {
            $this->accessToken = $parts[1];
            $this->uuid = $parts[2];
        } else {
            $this->accessToken = $this->sessionId;
        }
    }

}
