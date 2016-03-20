<?php
namespace api\models;

use api\models\base\ApiForm;
use api\models\base\PasswordProtectedForm;
use common\models\Account;
use Yii;
use yii\helpers\ArrayHelper;

class ChangePasswordForm extends PasswordProtectedForm {

    public $newPassword;

    public $newRePassword;

    /**
     * @var \common\models\Account
     */
    private $_account;

    /**
     * @inheritdoc
     */
    public function rules() {
        return ArrayHelper::merge(parent::rules(), [
            ['newPassword', 'required', 'message' => 'error.newPassword_required'],
            ['newRePassword', 'required', 'message' => 'error.newRePassword_required'],
            ['newPassword', 'string', 'min' => 8, 'tooShort' => 'error.password_too_short'],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
        ]);
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

    protected function getAccount() {
        return $this->_account;
    }

    /**
     * @param Account $account
     * @param array  $config
     */
    public function __construct(Account $account, array $config = []) {
        $this->_account = $account;
        parent::__construct($config);
    }

}
