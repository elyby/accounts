<?php
namespace api\modules\login\models;

use common\models\Account;
use Yii;
use yii\base\Model;

class AuthenticationForm extends Model {

    public $email;
    public $password;
    public $rememberMe = true;

    private $_user;

    public function rules() {
        return [
            ['email', 'required', 'message' => 'error.email_required'],
            ['email', 'email', 'message' => 'error.email_invalid'],
            ['email', 'validateEmail'],

            ['password', 'required', 'message' => 'error.password_required'],
            ['password', 'validatePassword'],

            ['rememberMe', 'boolean'],
        ];
    }

    public function validateEmail($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === NULL) {
                $this->addError($attribute, 'error.email_not_exist');
            }
        }
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if (!$account || !$account->validatePassword($this->password)) {
                $this->addError($attribute, 'error.password_incorrect');
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
        if ($this->_user === NULL) {
            $this->_user = Account::findByEmail($this->email);
        }

        return $this->_user;
    }

}
