<?php
namespace common\validators;

use yii\validators\StringValidator;

/**
 * Класс должен реализовывать в себе все критерии валидации пароля пользователя
 */
class PasswordValidate extends StringValidator {

    public $min = 8;

    public $tooShort = 'error.password_too_short';

}
