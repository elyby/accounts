<?php
namespace api\models\authentication;

use api\models\AccountIdentity;
use api\models\base\ApiForm;
use common\helpers\Error as E;
use api\traits\AccountFinder;
use common\models\Account;
use Yii;

/**
 * @method AccountIdentity|null getAccount()
 */
class LoginForm extends ApiForm {
    use AccountFinder;

    public $login;
    public $password;
    public $rememberMe = false;

    public function rules() {
        return [
            ['login', 'required', 'message' => E::LOGIN_REQUIRED],
            ['login', 'validateLogin'],

            ['password', 'required', 'when' => function(self $model) {
                return !$model->hasErrors();
            }, 'message' => E::PASSWORD_REQUIRED],
            ['password', 'validatePassword'],

            ['login', 'validateActivity'],

            ['rememberMe', 'boolean'],
        ];
    }

    public function validateLogin($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === null) {
                $this->addError($attribute, E::LOGIN_NOT_EXIST);
            }
        }
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account === null || !$account->validatePassword($this->password)) {
                $this->addError($attribute, E::PASSWORD_INCORRECT);
            }
        }
    }

    public function validateActivity($attribute) {
        // TODO: проверить, не заблокирован ли аккаунт
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account->status === Account::STATUS_BANNED) {
                $this->addError($attribute, E::ACCOUNT_BANNED);
            }

            if ($account->status === Account::STATUS_REGISTERED) {
                $this->addError($attribute, E::ACCOUNT_NOT_ACTIVATED);
            }
        }
    }

    public function getLogin() {
        return $this->login;
    }

    /**
     * @return \api\components\User\LoginResult|bool
     */
    public function login() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        if ($account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY) {
            $account->setPassword($this->password);
            $account->save();
        }

        return Yii::$app->user->login($account, $this->rememberMe);
    }

    protected function getAccountClassName() {
        return AccountIdentity::class;
    }

}
