<?php
namespace common\components;


/**
 * Этот класс был использован для изначальной генерации паролей на Ely.by и сейчас должен быть планомерно выпилен
 * с проекта с целью заменить этот алгоритм каким-нибудь посерьёзнее.
 */
class UserPass {

    public static function make($email, $pass) {
        return md5($pass . md5(strtolower($email)));
    }

}
