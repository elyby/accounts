<?php
namespace api\models;

use api\models\base\PasswordProtectedForm;
use common\models\Account;
use common\validators\PasswordValidate;
use Yii;
use yii\helpers\ArrayHelper;

class ChangePasswordForm extends PasswordProtectedForm {

    public $newPassword;

    public $newRePassword;

    public $logoutAll;

    /**
     * @var \common\models\Account
     */
    private $_account;

    /**
     * @inheritdoc
     */
    public function rules() {
        return ArrayHelper::merge(parent::rules(), [
            [['newPassword', 'newRePassword'], 'required', 'message' => 'error.{attribute}_required'],
            ['newPassword', PasswordValidate::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
            ['logoutAll', 'boolean'],
        ]);
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors($attribute)) {
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

        if ($this->logoutAll) {
            // TODO: реализовать процесс разлогинивания всех авторизованных устройств и дописать под это всё тесты
        }

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
