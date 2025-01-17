<?php
declare(strict_types=1);

namespace api\modules\accounts\models;

use api\components\User\Component;
use api\validators\PasswordRequiredValidator;
use api\validators\TotpValidator;
use common\helpers\Error as E;
use Webmozart\Assert\Assert;
use Yii;

class EnableTwoFactorAuthForm extends AccountActionForm {

    public mixed $totp = null;

    public mixed $password = null;

    public function rules(): array {
        return [
            ['account', $this->validateOtpDisabled(...)],
            ['totp', 'required', 'message' => E::TOTP_REQUIRED],
            ['totp', TotpValidator::class, 'account' => $this->getAccount()],
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
        Assert::true($account->save(), 'Cannot enable otp for account');

        Yii::$app->user->terminateSessions($account, Component::KEEP_CURRENT_SESSION);

        $transaction->commit();

        return true;
    }

    private function validateOtpDisabled(string $attribute): void {
        if ($this->getAccount()->is_otp_enabled) {
            $this->addError($attribute, E::OTP_ALREADY_ENABLED);
        }
    }

}
