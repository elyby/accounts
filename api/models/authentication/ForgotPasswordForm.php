<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\base\ApiForm;
use common\components\UserFriendlyRandomKey;
use common\helpers\Error as E;
use common\models\Account;
use common\models\confirmations\ForgotPassword;
use common\models\EmailActivation;
use common\tasks\SendPasswordRecoveryEmail;
use Yii;
use yii\base\ErrorException;

class ForgotPasswordForm extends ApiForm {

    public mixed $captcha = null;

    public mixed $login = null;

    public function rules(): array {
        return [
            ['captcha', ReCaptchaValidator::class],
            ['login', 'required', 'message' => E::LOGIN_REQUIRED],
            ['login', 'validateLogin'],
            ['login', 'validateActivity'],
            ['login', 'validateFrequency'],
        ];
    }

    public function validateLogin(string $attribute): void {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === null) {
                $this->addError($attribute, E::LOGIN_NOT_EXIST);
            }
        }
    }

    public function validateActivity(string $attribute): void {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account->status !== Account::STATUS_ACTIVE) {
                $this->addError($attribute, E::ACCOUNT_NOT_ACTIVATED);
            }
        }
    }

    public function validateFrequency(string $attribute): void {
        if (!$this->hasErrors()) {
            $emailConfirmation = $this->getEmailActivation();
            if ($emailConfirmation !== null && !$emailConfirmation->canResend()) {
                $this->addError($attribute, E::RECENTLY_SENT_MESSAGE);
            }
        }
    }

    public function getAccount(): ?Account {
        return Account::find()->andWhereLogin($this->login)->one();
    }

    public function forgotPassword(): bool {
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

        Yii::$app->queue->push(SendPasswordRecoveryEmail::createFromConfirmation($emailActivation));

        return true;
    }

    public function getEmailActivation(): ?ForgotPassword {
        $account = $this->getAccount();
        if ($account === null) {
            return null;
        }

        // @phpstan-ignore return.type
        return $account->getEmailActivations()->withType(EmailActivation::TYPE_FORGOT_PASSWORD_KEY)->one();
    }

}
