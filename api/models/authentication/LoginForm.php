<?php
namespace api\models\authentication;

use api\models\base\ApiForm;
use api\traits\AccountFinder;
use common\models\Account;
use Yii;

class LoginForm extends ApiForm {
    use AccountFinder;

    public $login;
    public $password;
    public $rememberMe = true;

    public function rules() {
        return [
            ['login', 'required', 'message' => 'error.login_required'],
            ['login', 'validateLogin'],

            ['password', 'required', 'when' => function(self $model) {
                return !$model->hasErrors();
            }, 'message' => 'error.password_required'],
            ['password', 'validatePassword'],

            ['login', 'validateActivity'],

            ['rememberMe', 'boolean'],
        ];
    }

    public function validateLogin($attribute) {
        if (!$this->hasErrors()) {
            if (!$this->getAccount()) {
                $this->addError($attribute, 'error.' . $attribute . '_not_exist');
            }
        }
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if (!$account || !$account->validatePassword($this->password)) {
                $this->addError($attribute, 'error.' . $attribute . '_incorrect');
            }
        }
    }

    public function validateActivity($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account->status !== Account::STATUS_ACTIVE) {
                $this->addError($attribute, 'error.account_not_activated');
            }
        }
    }

    public function getLogin() {
        return $this->login;
    }

    /**
     * @return bool|string JWT с информацией об аккаунте
     */
    public function login() {
        if (!$this->validate()) {
            return false;
        }

        if ($this->rememberMe) {
            // TODO: здесь нужно записать какую-то
        }

        $account = $this->getAccount();
        if ($account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY) {
            $account->setPassword($this->password);
            $account->save();
        }

        return $account->getJWT();
    }

}
