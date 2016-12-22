<?php
namespace api\models\authentication;

use api\models\AccountIdentity;
use api\models\base\ApiForm;
use api\validators\EmailActivationKeyValidator;
use common\helpers\Error as E;
use common\models\EmailActivation;
use common\validators\PasswordValidator;
use Yii;
use yii\base\ErrorException;

class RecoverPasswordForm extends ApiForm {

    public $key;

    public $newPassword;

    public $newRePassword;

    public function rules() {
        return [
            ['key', EmailActivationKeyValidator::class, 'type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY],
            ['newPassword', 'required', 'message' => E::NEW_PASSWORD_REQUIRED],
            ['newRePassword', 'required', 'message' => E::NEW_RE_PASSWORD_REQUIRED],
            ['newPassword', PasswordValidator::class],
            ['newRePassword', 'validatePasswordAndRePasswordMatch'],
        ];
    }

    public function validatePasswordAndRePasswordMatch($attribute) {
        if (!$this->hasErrors()) {
            if ($this->newPassword !== $this->newRePassword) {
                $this->addError($attribute, E::NEW_RE_PASSWORD_DOES_NOT_MATCH);
            }
        }
    }

    /**
     * @return \api\components\User\LoginResult|bool
     * @throws ErrorException
     */
    public function recoverPassword() {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var \common\models\confirmations\ForgotPassword $confirmModel */
        $confirmModel = $this->key;
        $account = $confirmModel->account;
        $account->password = $this->newPassword;
        if (!$confirmModel->delete()) {
            throw new ErrorException('Unable remove activation key.');
        }

        if (!$account->save(false)) {
            throw new ErrorException('Unable activate user account.');
        }

        $transaction->commit();

        return Yii::$app->user->login(new AccountIdentity($account->attributes), false);
    }

}
