<?php
namespace api\models\authentication;

use api\models\AccountIdentity;
use api\models\base\KeyConfirmationForm;
use common\helpers\Error as E;
use common\models\EmailActivation;
use common\validators\PasswordValidate;
use Yii;
use yii\base\ErrorException;

class RecoverPasswordForm extends KeyConfirmationForm {

    public $newPassword;

    public $newRePassword;

    public function rules() {
        return array_merge(parent::rules(), [
            ['newPassword', 'required', 'message' => E::NEW_PASSWORD_REQUIRED],
            ['newRePassword', 'required', 'message' => E::NEW_RE_PASSWORD_REQUIRED],
            ['newPassword', PasswordValidate::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
        ]);
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors()) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, E::NEW_RE_PASSWORD_DOES_NOT_MATCH);
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

        // TODO: ещё было бы неплохо уведомить пользователя о том, что его пароль изменился

        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;

        return $component->login(new AccountIdentity($account->attributes), false);
    }

}
