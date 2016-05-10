<?php
namespace common\helpers;

class StringHelper {

    public static function getEmailMask($email) {
        $username = explode('@', $email)[0];
        $usernameLength = mb_strlen($username);
        $maskChars = '**';

        if ($usernameLength === 1) {
            $mask = $maskChars;
        } elseif($usernameLength === 2) {
            $mask = mb_substr($username, 0, 1) . $maskChars;
        } elseif($usernameLength === 3) {
            $mask = mb_substr($username, 0, 1) . $maskChars . mb_substr($username, 2, 1);
        } else {
            $mask = mb_substr($username, 0, 2) . $maskChars . mb_substr($username, -2, 2);
        }

        return $mask . mb_substr($email, $usernameLength);
    }

}
