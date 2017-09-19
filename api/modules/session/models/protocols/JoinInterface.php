<?php
namespace api\modules\session\models\protocols;

interface JoinInterface {

    public function getAccessToken(): string;

    public function getSelectedProfile(): string;

    public function getServerId(): string;

    public function validate(): bool;

}
