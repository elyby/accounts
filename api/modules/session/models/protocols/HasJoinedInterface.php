<?php
namespace api\modules\session\models\protocols;

interface HasJoinedInterface {

    public function getUsername(): string;

    public function getServerId(): string;

    public function validate(): bool;

}
