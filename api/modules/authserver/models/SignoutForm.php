<?php
namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\helpers\Error as E;
use common\models\MinecraftAccessKey;
use Yii;

class SignoutForm extends ApiForm {

    public $username;

    public $password;

    public function rules() {
        return [
            [['username', 'password'], RequiredValidator::class],
        ];
    }

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
