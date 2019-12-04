<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\Module as Authserver;
use api\modules\authserver\validators\ClientTokenValidator;
use api\modules\authserver\validators\RequiredValidator;
use common\helpers\Error as E;
use common\models\Account;
use Yii;

class AuthenticationForm extends ApiForm {

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $clientToken;

    public function rules(): array {
        return [
            [['username', 'password', 'clientToken'], RequiredValidator::class],
            [['clientToken'], ClientTokenValidator::class],
        ];
    }

    /**
     * @return AuthenticateData
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     */
    public function authenticate(): AuthenticateData {
        // This validating method will throw an exception in case when validation will not pass successfully
        $this->validate();

        Authserver::info("Trying to authenticate user by login = '{$this->username}'.");

        $loginForm = new LoginForm();
        $loginForm->login = $this->username;
        $loginForm->password = $this->password;
        if (!$loginForm->validate()) {
            $errors = $loginForm->getFirstErrors();
            if (isset($errors['totp'])) {
                Authserver::error("User with login = '{$this->username}' protected by two factor auth.");
                throw new ForbiddenOperationException('Account protected with two factor auth.');
            }

            if (isset($errors['login'])) {
                if ($errors['login'] === E::ACCOUNT_BANNED) {
                    Authserver::error("User with login = '{$this->username}' is banned");
                    throw new ForbiddenOperationException('This account has been suspended.');
                }

                Authserver::error("Cannot find user by login = '{$this->username}'");
            } elseif (isset($errors['password'])) {
                Authserver::error("User with login = '{$this->username}' passed wrong password.");
            }

            // The previous authorization server implementation used the nickname field instead of username,
            // so we keep such behavior
            $attribute = $loginForm->getLoginAttribute();
            if ($attribute === 'username') {
                $attribute = 'nickname';
            }

            // TODO: эта логика дублируется с логикой в SignoutForm

            throw new ForbiddenOperationException("Invalid credentials. Invalid {$attribute} or password.");
        }

        /** @var Account $account */
        $account = $loginForm->getAccount();
        $token = Yii::$app->tokensFactory->createForMinecraftAccount($account, $this->clientToken);
        $dataModel = new AuthenticateData($account, (string)$token, $this->clientToken);

        Authserver::info("User with id = {$account->id}, username = '{$account->username}' and email = '{$account->email}' successfully logged in.");

        return $dataModel;
    }

}
