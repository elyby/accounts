<?php
namespace api\modules\session\models\protocols;

use yii\validators\RequiredValidator;

abstract class BaseHasJoined implements HasJoinedInterface {

    private $username;
    private $serverId;

    public function __construct(string $username, string $serverId) {
        $this->username = $username;
        $this->serverId = $serverId;
    }

    public function getUsername() : string {
        return $this->username;
    }

    public function getServerId() : string {
        return $this->serverId;
    }

    public function validate() : bool {
        $validator = new RequiredValidator();

        return $validator->validate($this->username)
            && $validator->validate($this->serverId);
    }

}
