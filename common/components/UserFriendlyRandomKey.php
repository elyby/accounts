<?php
declare(strict_types=1);

namespace common\components;

class UserFriendlyRandomKey {

    public static function make(int $length = 18) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $numChars = strlen($chars);
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[random_int(0, $numChars - 1)];
        }

        return $key;
    }

}
