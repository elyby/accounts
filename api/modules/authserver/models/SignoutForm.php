<?php
namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\models\MinecraftAccessKey;
use Yii;

class SignoutForm extends Form {

    public $username;
    public $password;

    public function rules() {
        return [
            [['username', 'password'], RequiredValidator::class],
        ];
    }

    public function signout() : bool {
        $this->validate();

        $loginForm = new LoginForm();
        $loginForm->login = $this->username;
        $loginForm->password = $this->password;
        if (!$loginForm->validate()) {
            // На старом сервере авторизации использовалось поле nickname, а не username, так что сохраняем эту логику
            $attribute = $loginForm->getLoginAttribute();
            if ($attribute === 'username') {
                $attribute = 'nickname';
            }

            throw new ForbiddenOperationException("Invalid credentials. Invalid {$attribute} or password.");
        }

        $account = $loginForm->getAccount();

        /** @noinspection SqlResolve */
        Yii::$app->db->createCommand('
            DELETE
              FROM ' . MinecraftAccessKey::tableName() . '
             WHERE account_id = :userId
        ', [
            'userId' => $account->id,
        ])->execute();

        return true;
    }

}
