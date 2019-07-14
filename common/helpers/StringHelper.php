<?php
declare(strict_types=1);
namespace common\helpers;

use Ramsey\Uuid\Uuid;

class StringHelper {

    public static function getEmailMask(string $email): string {
        $username = explode('@', $email)[0];
        $usernameLength = mb_strlen($username);
        $maskChars = '**';

        if ($usernameLength === 1) {
            $mask = $maskChars;
        } elseif ($usernameLength === 2) {
            $mask = mb_substr($username, 0, 1) . $maskChars;
        } elseif ($usernameLength === 3) {
            $mask = mb_substr($username, 0, 1) . $maskChars . mb_substr($username, 2, 1);
        } else {
            $mask = mb_substr($username, 0, 2) . $maskChars . mb_substr($username, -2, 2);
        }

        return $mask . mb_substr($email, $usernameLength);
    }

    public static function isUuid(string $uuid): bool {
        try {
            Uuid::fromString($uuid);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns a string with whitespace removed from the start and end of the
     * string. Supports the removal of unicode whitespace.
     *
     * Based on the http://markushedlund.com/dev/trim-unicodeutf-8-whitespace-in-php
     *
     * @param  string $string string to remove whitespaces
     * @return string trimmed $string
     */
    public static function trim(?string $string): string {
        if ($string === null) {
            return '';
        }

        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $string);
    }

}
