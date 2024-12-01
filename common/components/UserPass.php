<?php
namespace common\components;

/**
 * This class was used for the first generation of passwords on the Ely.by
 * and should now be systematically cut from the project in order to replace this algorithm
 * with a more secure one.
 */
class UserPass {

    public static function make($email, string $pass): string {
        return md5($pass . md5(strtolower((string)$email)));
    }

}
