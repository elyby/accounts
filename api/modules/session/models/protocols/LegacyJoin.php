<?php
namespace api\modules\session\models\protocols;

use yii\validators\RequiredValidator;

class LegacyJoin extends BaseJoin {

    private $user;
    private $sessionId;
    private $serverId;

    private $accessToken;
    private $uuid;

    public function __construct(string $user, string $sessionId, string $serverId) {
        $this->user = $user;
        $this->sessionId = $sessionId;
        $this->serverId = $serverId;

        $this->parseSessionId($this->sessionId);
    }

    public function getAccessToken() : string {
        return $this->accessToken;
    }

    public function getSelectedProfile() : string {
        return $this->uuid ?: $this->user;
    }

    public function getServerId() : string {
        return $this->serverId;
    }

    /**
     * @return bool
     */
    public function validate() : bool {
        $validator = new RequiredValidator();

        return $validator->validate($this->accessToken)
            && $validator->validate($this->user)
            && $validator->validate($this->serverId);
    }

    /**
     * Метод проводит инициализацию значений полей для соотвествия общим канонам
     * именования в проекте
     *
     * Бьём по ':' для учёта авторизации в современных лаунчерах и входе на более старую
     * версию игры. Там sessionId передаётся как "token:{accessToken}:{uuid}", так что это нужно обработать
     */
    protected function parseSessionId(string $sessionId) {
        $parts = explode(':', $sessionId);
        if (count($parts) === 3) {
            $this->accessToken = $parts[1];
            $this->uuid = $parts[2];
        } else {
            $this->accessToken = $this->sessionId;
        }
    }

}
