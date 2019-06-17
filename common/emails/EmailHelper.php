<?php
declare(strict_types=1);

namespace common\emails;

class EmailHelper {

    public static function buildTo(string $username, string $email): array {
        return [$email => $username];
    }

}
