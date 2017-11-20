<?php
namespace api\modules\accounts\models;

use api\components\User\Component;
use api\exceptions\ThisShouldNotHappenException;
use api\validators\PasswordRequiredValidator;
use api\validators\TotpValidator;
use common\helpers\Error as E;
use Yii;

class EnableTwoFactorAuthForm extends AccountActionForm {

    public $totp;

    public $password;

    public function rules(): array {
        return [
            ['account', 'validateOtpDisabled'],
            ['totp', 'required', 'message' => E::TOTP_REQUIRED],
            ['totp', TotpValidator::class, 'account' => $this->getAccount(), 'window' => 2],
            ['password', PasswordRequiredValidator::class, 'account' => $this->getAccount()],
        ];
    }

    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $transaction = Yii::$app->db->beginTransaction();

        $account = $this->getAccount();
        $account->is_otp_enabled = true;
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot enable otp for account');
        }

        Yii::$app->user->terminateSessions($account, Component::KEEP_CURRENT_SESSION);

        $transaction->commit();

        return true;
    }

    public function validateOtpDisabled($attribute): void {
        if ($this->getAccount()->is_otp_enabled) {
            $this->addError($attribute, E::OTP_ALREADY_ENABLED);
        }
    }

}
