<?php
namespace api\models\authentication;

use common\emails\EmailHelper;
use api\models\base\ApiForm;
use api\validators\TotpValidator;
use common\helpers\Error as E;
use api\traits\AccountFinder;
use common\components\UserFriendlyRandomKey;
use common\models\Account;
use common\models\confirmations\ForgotPassword;
use common\models\EmailActivation;
use yii\base\ErrorException;

class ForgotPasswordForm extends ApiForm {
    use AccountFinder;

    public $login;
    public $token;

    public function rules() {
        return [
            ['login', 'required', 'message' => E::LOGIN_REQUIRED],
            ['login', 'validateLogin'],
            ['token', 'required', 'when' => function(self $model) {
                return !$this->hasErrors() && $model->getAccount()->is_otp_enabled;
            }, 'message' => E::OTP_TOKEN_REQUIRED],
            ['token', 'validateTotpToken'],
            ['login', 'validateActivity'],
            ['login', 'validateFrequency'],
        ];
    }

    public function validateLogin($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === null) {
                $this->addError($attribute, E::LOGIN_NOT_EXIST);
            }
        }
    }

    public function validateTotpToken($attribute) {
        if ($this->hasErrors()) {
            return;
        }

        $account = $this->getAccount();
        if (!$account->is_otp_enabled) {
            return;
        }

        $validator = new TotpValidator(['account' => $account]);
        $validator->window = 1;
        $validator->validateAttribute($this, $attribute);
    }

    public function validateActivity($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account->status !== Account::STATUS_ACTIVE) {
                $this->addError($attribute, E::ACCOUNT_NOT_ACTIVATED);
            }
        }
    }

    public function validateFrequency($attribute) {
        if (!$this->hasErrors()) {
            $emailConfirmation = $this->getEmailActivation();
            if ($emailConfirmation !== null && !$emailConfirmation->canRepeat()) {
                $this->addError($attribute, E::RECENTLY_SENT_MESSAGE);
            }
        }
    }

    public function forgotPassword() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $emailActivation = $this->getEmailActivation();
        if ($emailActivation === null) {
            $emailActivation = new ForgotPassword();
            $emailActivation->account_id = $account->id;
        } else {
            $emailActivation->created_at = time();
        }

        $emailActivation->key = UserFriendlyRandomKey::make();
        if (!$emailActivation->save()) {
            throw new ErrorException('Cannot create email activation for forgot password form');
        }

        EmailHelper::forgotPassword($emailActivation);

        return true;
    }

    public function getLogin() {
        return $this->login;
    }

    /**
     * @return EmailActivation|null
     * @throws ErrorException
     */
    public function getEmailActivation() {
        $account = $this->getAccount();
        if ($account === null) {
            throw new ErrorException('Account not founded');
        }

        return $account->getEmailActivations()
            ->andWhere(['type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY])
            ->one();
    }

}
