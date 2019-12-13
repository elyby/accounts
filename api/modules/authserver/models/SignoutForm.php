<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\helpers\Error as E;

class SignoutForm extends ApiForm {

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    public function rules(): array {
        return [
            [['username', 'password'], RequiredValidator::class],
        ];
    }

    /**
     * @return bool
     * @throws ForbiddenOperationException
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function signout(): bool {
        $this->validate();

        $loginForm = new LoginForm();
        $loginForm->login = $this->username;
        $loginForm->password = $this->password;
        if (!$loginForm->validate()) {
            $errors = $loginForm->getFirstErrors();
            if (isset($errors['login']) && $errors['login'] === E::ACCOUNT_BANNED) {
                // We believe that a blocked one can get out painlessly
                return true;
            }

            // The previous authorization server implementation used the nickname field instead of username,
            // so we keep such behavior
            $attribute = strpos($this->username, '@') === false ? 'nickname' : 'email';

            throw new ForbiddenOperationException("Invalid credentials. Invalid {$attribute} or password.");
        }

        // We're unable to invalidate access tokens because they aren't stored in our database

        return true;
    }

}
