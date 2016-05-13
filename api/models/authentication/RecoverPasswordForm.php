<?php
namespace api\models\authentication;

use api\models\base\KeyConfirmationForm;
use common\models\EmailActivation;
use common\validators\PasswordValidate;
use Yii;
use yii\base\ErrorException;

class RecoverPasswordForm extends KeyConfirmationForm {

    public $newPassword;

    public $newRePassword;

    public function rules() {
        return array_merge(parent::rules(), [
            [['newPassword', 'newRePassword'], 'required', 'message' => 'error.{attribute}_required'],
            ['newPassword', PasswordValidate::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
        ]);
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors()) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, 'error.rePassword_does_not_match');
            }
        }
    }

    public function recoverPassword() {
        if (!$this->validate()) {
            return false;
        }

        $confirmModel = $this->getActivationCodeModel();
        if ($confirmModel->type !== EmailActivation::TYPE_FORGOT_PASSWORD_KEY) {
            $confirmModel->delete();
            // TODO: вот где-то здесь нужно ещё попутно сгенерировать соответствующую ошибку
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $account = $confirmModel->account;
            $account->password = $this->newPassword;
            if (!$confirmModel->delete()) {
                throw new ErrorException('Unable remove activation key.');
            }

            if (!$account->save()) {
                throw new ErrorException('Unable activate user account.');
            }

            $transaction->commit();
        } catch (ErrorException $e) {
            $transaction->rollBack();
            if (YII_DEBUG) {
                throw $e;
            } else {
                return false;
            }
        }

        // TODO: ещё было бы неплохо уведомить пользователя о том, что его E-mail изменился

        return $account->getJWT();
    }

}
