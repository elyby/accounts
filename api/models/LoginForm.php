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

    /**
     * Logs in a user using the provided username and password.
     *
     * @return boolean whether the user is logged in successfully
     */
    public function login() {
        if (!$this->validate()) {
            return false;
        }

        return Yii::$app->user->login($this->getAccount(), $this->rememberMe ? 3600 * 24 * 30 : 0);
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
