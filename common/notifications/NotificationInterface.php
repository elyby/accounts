<?php
declare(strict_types=1);

namespace common\notifications;

interface NotificationInterface {

    public static function getType(): string;

    public function getPayloads(): array;

}
