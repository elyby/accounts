<?php
namespace common\helpers;

use Ramsey\Uuid\Uuid;

class StringHelper {

    public static function getEmailMask(string $email) : string {
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

    /**
     * Проверяет на то, что переданная строка является валидным UUID
     *
     * @param string $uuid
     * @return bool
     */
    public static function isUuid(string $uuid) : bool {
        try {
            Uuid::fromString($uuid);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

}
