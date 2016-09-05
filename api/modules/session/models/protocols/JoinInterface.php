<?php
namespace api\modules\session\models\protocols;

interface JoinInterface {

    public function getAccessToken() : string;

    // TODO: после перехода на PHP 7.1 сменить тип на ?string и возвращать null, если параметр не передан
    public function getSelectedProfile() : string;

    public function getServerId() : string;

    public function validate() : bool;

}
