<?php
namespace api\modules\session\models\protocols;

class LegacyJoin extends BaseJoin {

    private $user;
    private $sessionId;
    private $serverId;

    private $accessToken;
    private $uuid;

    public function __construct(string $user, string $sessionId, string $serverId) {
        $this->user = trim($user);
        $this->sessionId = trim($sessionId);
        $this->serverId = trim($serverId);

        $this->parseSessionId($this->sessionId);
    }

    public function getAccessToken() : string {
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
     * Метод проводит инициализацию значений полей для соотвествия общим канонам
     * именования в проекте
     *
     * Бьём по ':' для учёта авторизации в современных лаунчерах и входе на более старую
     * версию игры. Там sessionId передаётся как "token:{accessToken}:{uuid}", так что это нужно обработать
     */
    private function parseSessionId(string $sessionId) {
        $parts = explode(':', $sessionId);
        if (count($parts) === 3) {
            $this->accessToken = $parts[1];
            $this->uuid = $parts[2];
        } else {
            $this->accessToken = $this->sessionId;
        }
    }

}
