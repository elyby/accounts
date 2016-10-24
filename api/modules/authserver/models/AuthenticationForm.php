<?php
namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\Module as Authserver;
use api\modules\authserver\validators\RequiredValidator;
use common\helpers\Error as E;
use common\models\Account;
use common\models\MinecraftAccessKey;

class AuthenticationForm extends Form {

    public $username;
    public $password;
    public $clientToken;

    public function rules() {
        return [
            [['username', 'password', 'clientToken'], RequiredValidator::class],
        ];
    }

    /**
     * @return AuthenticateData
     * @throws \api\modules\authserver\exceptions\AuthserverException
     */
    public function authenticate() {
        $this->validate();

        Authserver::info("Trying to authenticate user by login = '{$this->username}'.");

        $loginForm = $this->createLoginForm();
        $loginForm->login = $this->username;
        $loginForm->password = $this->password;
        if (!$loginForm->validate()) {
            $errors = $loginForm->getFirstErrors();
            if (isset($errors['login'])) {
                if ($errors['login'] === E::ACCOUNT_BANNED) {
                    Authserver::error("User with login = '{$this->username}' is banned");
                    throw new ForbiddenOperationException('This account has been suspended.');
                } else {
                    Authserver::error("Cannot find user by login = '{$this->username}'");
                }
            } elseif (isset($errors['password'])) {
                Authserver::error("User with login = '{$this->username}' passed wrong password.");
            }

            // На старом сервере авторизации использовалось поле nickname, а не username, так что сохраняем эту логику
            $attribute = $loginForm->getLoginAttribute();
            if ($attribute === 'username') {
                $attribute = 'nickname';
            }

            // TODO: эта логика дублируется с логикой в SignoutForm

            throw new ForbiddenOperationException("Invalid credentials. Invalid {$attribute} or password.");
        }

        $account = $loginForm->getAccount();
        $accessTokenModel = $this->createMinecraftAccessToken($account);
        $dataModel = new AuthenticateData($accessTokenModel);

        Authserver::info("User with id = {$account->id}, username = '{$account->username}' and email = '{$account->email}' successfully logged in.");

        return $dataModel;
    }

    protected function createMinecraftAccessToken(Account $account) : MinecraftAccessKey {
        /** @var MinecraftAccessKey|null $accessTokenModel */
        $accessTokenModel = MinecraftAccessKey::findOne([
            'account_id' => $account->id,
            'client_token' => $this->clientToken,
        ]);

        if ($accessTokenModel === null) {
            $accessTokenModel = new MinecraftAccessKey();
            $accessTokenModel->client_token = $this->clientToken;
            $accessTokenModel->account_id = $account->id;
            $accessTokenModel->insert();
        } else {
            $accessTokenModel->refreshPrimaryKeyValue();
            $accessTokenModel->update();
        }

        return $accessTokenModel;
    }

    protected function createLoginForm() : LoginForm {
        return new LoginForm();
    }

}
