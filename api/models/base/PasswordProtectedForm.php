<?php
namespace api\models\base;

use common\helpers\Error as E;
use Yii;

class PasswordProtectedForm extends ApiForm {

    public $password;

    public function rules() {
        return [
            [['password'], 'required', 'message' => E::PASSWORD_REQUIRED],
            [['password'], 'validatePassword'],
        ];
    }

    public function validatePassword() {
        if (!$this->getAccount()->validatePassword($this->password)) {
            $this->addError('password', E::PASSWORD_INVALID);
        }
    }

    /**
     * @return \common\models\Account
     */
    protected function getAccount() {
        return Yii::$app->user->identity;
    }

}
