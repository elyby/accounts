<?php
namespace common\validators;

use common\helpers\Error as E;
use yii\validators\StringValidator;

class PasswordValidator extends StringValidator {

    public $min = 8;

    public $tooShort = E::PASSWORD_TOO_SHORT;

}
