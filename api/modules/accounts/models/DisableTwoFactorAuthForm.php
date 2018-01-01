<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use api\exceptions\ThisShouldNotHappenException;
use api\validators\PasswordRequiredValidator;
use api\validators\TotpValidator;
use common\helpers\Error as E;

class DisableTwoFactorAuthForm extends AccountActionForm {

    public $totp;

    public $password;

    public function rules(): array {
        return [
            ['account', 'validateOtpEnabled'],
            ['totp', 'required', 'message' => E::TOTP_REQUIRED],
            ['totp', TotpValidator::class, 'account' => $this->getAccount()],
            ['password', PasswordRequiredValidator::class, 'account' => $this->getAccount()],
        ];
    }

    /**
     * @CollectModelMetrics(prefix="accounts.disableTwoFactorAuth")
     */
    public function performAction(): bool {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $account->is_otp_enabled = false;
        $account->otp_secret = null;
        if (!$account->save()) {
            throw new ThisShouldNotHappenException('Cannot disable otp for account');
        }

        return true;
    }

    public function validateOtpEnabled($attribute): void {
        if (!$this->getAccount()->is_otp_enabled) {
            $this->addError($attribute, E::OTP_NOT_ENABLED);
        }
    }

}
