<?php
namespace api\models\base;

use Yii;

class PasswordProtectedForm extends ApiForm {

    public $password;

    public function rules() {
        return [
            [['password'], 'required', 'message' => 'error.password_required'],
            [['password'], 'validatePassword'],
        ];
    }

    public function validatePassword() {
        if (!$this->getAccount()->validatePassword($this->password)) {
            $this->addError('password', 'error.password_invalid');
        }
    }

    /**
     * @return \common\models\Account
     */
    protected function getAccount() {
        return Yii::$app->user->identity;
    }

}
