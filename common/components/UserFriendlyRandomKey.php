<?php
namespace common\components;


class UserFriendlyRandomKey {

    public static function make ($length = 18) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $numChars = strlen($chars);
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= substr($chars, rand(1, $numChars) - 1, 1);
        }

        return $key;
    }

}
