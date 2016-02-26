<?php
namespace api\models;

use common\models\Account;
use Yii;

class ChangePasswordForm extends BaseApiForm {

    public $password;

    public $newPassword;

    public $newRePassword;

    /**
     * @var \common\models\Account
     */
    private $_account;

    /**
     * @param Account $account
     * @param array  $config
     */
    public function __construct(Account $account, array $config = []) {
        $this->_account = $account;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            ['password', 'required', 'message' => 'error.password_required'],
            ['newPassword', 'required', 'message' => 'error.newPassword_required'],
            ['newRePassword', 'required', 'message' => 'error.newRePassword_required'],
            ['password', 'validatePassword'],
            ['newPassword', 'string', 'min' => 8, 'tooShort' => 'error.password_too_short'],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
        ];
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors() && !$this->_account->validatePassword($this->$attribute)) {
            $this->addError($attribute, 'error.' . $attribute . '_incorrect');
        }
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors()) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, 'error.newRePassword_does_not_match');
            }
        }
    }

    /**
     * @return boolean if password was changed.
     */
    public function changePassword() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->_account;
        $account->setPassword($this->newPassword);

        return $account->save();
    }

}
