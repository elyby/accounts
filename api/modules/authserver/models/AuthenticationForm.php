<?php
namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\models\MinecraftAccessKey;
use Yii;

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

        Yii::info("Trying to authenticate user by login = '{$this->username}'.", 'legacy-authentication');

        $loginForm = new LoginForm();
        $loginForm->login = $this->username;
        $loginForm->password = $this->password;
        if (!$loginForm->validate()) {
            $errors = $loginForm->getFirstErrors();
            if (isset($errors['login'])) {
                Yii::error("Cannot find user by login = '{$this->username}", 'legacy-authentication');
            } elseif (isset($errors['password'])) {
                Yii::error("User with login = '{$this->username}' passed wrong password.", 'legacy-authentication');
            }

            // На старом сервере авторизации использовалось поле nickname, а не username, так что сохраняем эту логику
            $attribute = $loginForm->getLoginAttribute();
            if ($attribute === 'username') {
                $attribute = 'nickname';
            }

            // TODO: если аккаунт заблокирован, то возвращалось сообщение return "This account has been suspended."
            // TODO: эта логика дублируется с логикой в SignoutForm

            throw new ForbiddenOperationException("Invalid credentials. Invalid {$attribute} or password.");
        }

        $account = $loginForm->getAccount();

        /** @var MinecraftAccessKey|null $accessTokenModel */
        $accessTokenModel = MinecraftAccessKey::findOne(['client_token' => $this->clientToken]);
        if ($accessTokenModel === null) {
            $accessTokenModel = new MinecraftAccessKey();
            $accessTokenModel->client_token = $this->clientToken;
            $accessTokenModel->account_id = $account->id;
            $accessTokenModel->insert();
        } else {
            $accessTokenModel->refreshPrimaryKeyValue();
        }

        $dataModel = new AuthenticateData($accessTokenModel);

        Yii::info("User with id = {$account->id}, username = '{$account->username}' and email = '{$account->email}' successfully logged in.", 'legacy-authentication');

        return $dataModel;
    }

}
