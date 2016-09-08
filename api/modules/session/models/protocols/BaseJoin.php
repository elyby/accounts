<?php
namespace api\modules\session\models\protocols;

abstract class BaseJoin implements JoinInterface {

    abstract public function getAccessToken() : string;

    abstract public function getSelectedProfile() : string;

    abstract public function getServerId() : string;

    abstract public function validate() : bool;

}
