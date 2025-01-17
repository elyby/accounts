<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\base\ApiForm;
use api\modules\authserver\validators\RequiredValidator;

final class SignoutForm extends ApiForm {

    public mixed $username = null;

    public mixed $password = null;

    public function rules(): array {
        return [
            [['username', 'password'], RequiredValidator::class],
        ];
    }

    public function signout(): bool {
        $this->validate();

        // We're unable to invalidate access tokens because they aren't stored in our database
        // We don't give an error about invalid credentials to eliminate a point through which attackers can brut force passwords.

        return true;
    }

}
