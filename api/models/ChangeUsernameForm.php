<?php
namespace api\models;

use api\models\base\PasswordProtectedForm;
use common\models\Account;
use Yii;
use yii\helpers\ArrayHelper;

class ChangeUsernameForm extends PasswordProtectedForm {

    public $username;

    public function rules() {
        return ArrayHelper::merge(parent::rules(), [
            [['username'], 'required', 'message' => 'error.{attribute}_required'],
            [['username'], 'validateUsername'],
        ]);
    }

    public function validateUsername($attribute) {
        $account = new Account();
        $account->username = $this->$attribute;
        if (!$account->validate(['username'])) {
            $this->addErrors($account->getErrors());
        }
    }

    public function change() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $account->username = $this->username;

        return $account->save();
    }

}
