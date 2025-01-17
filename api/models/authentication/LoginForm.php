<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\models\base\ApiForm;
use common\helpers\Error as E;

final class LoginForm extends ApiForm {

    public mixed $login = null;

    public mixed $password = null;

    public mixed $totp = null;

    public mixed $rememberMe = false;

    public function rules(): array {
        return [
            ['login', 'required', 'message' => E::LOGIN_REQUIRED],
            ['password', 'required', 'isEmpty' => fn($value) => $value === null, 'message' => E::PASSWORD_REQUIRED],
            ['totp', 'string'],
            ['rememberMe', 'boolean'],
        ];
    }

}
