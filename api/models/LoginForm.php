<?php
namespace api\models;

use common\models\Account;
use Yii;

class LoginForm extends BaseApiForm {

    public $login;
    public $password;
    public $rememberMe = true;

    private $_account;

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

    /**
     * @return bool|string JWT с информацией об аккаунте
     */
    public function login() {
        if (!$this->validate()) {
            return false;
        }

        return $this->getAccount()->getJWT();
    }

    /**
     * @return Account|null
     */
    protected function getAccount() {
        if ($this->_account === NULL) {
            $attribute = strpos($this->login, '@') ? 'email' : 'username';
            $this->_account = Account::findOne([$attribute => $this->login]);
        }

        return $this->_account;
    }

}
