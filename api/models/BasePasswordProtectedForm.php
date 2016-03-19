<?php
namespace api\models;

use Yii;

class BasePasswordProtectedForm extends BaseApiForm {

    public $password;

    public function rules() {
        return [
            [['password'], 'required', 'message' => 'error.{attribute}_required'],
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
