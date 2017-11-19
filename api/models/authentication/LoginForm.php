<?php
namespace api\models\authentication;

use api\aop\annotations\CollectModelMetrics;
use api\models\base\ApiForm;
use api\validators\TotpValidator;
use common\helpers\Error as E;
use api\traits\AccountFinder;
use common\models\Account;
use Yii;

class LoginForm extends ApiForm {
    use AccountFinder;

    public $login;
    public $password;
    public $totp;
    public $rememberMe = false;

    public function rules(): array {
        return [
            ['login', 'required', 'message' => E::LOGIN_REQUIRED],
            ['login', 'validateLogin'],

            ['password', 'required', 'when' => function(self $model) {
                return !$model->hasErrors();
            }, 'message' => E::PASSWORD_REQUIRED],
            ['password', 'validatePassword'],

            ['totp', 'required', 'when' => function(self $model) {
                return !$model->hasErrors() && $model->getAccount()->is_otp_enabled;
            }, 'message' => E::TOTP_REQUIRED],
            ['totp', 'validateTotp'],

            ['login', 'validateActivity'],

            ['rememberMe', 'boolean'],
        ];
    }

    public function validateLogin($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === null) {
                $this->addError($attribute, E::LOGIN_NOT_EXIST);
            }
        }
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account === null || !$account->validatePassword($this->password)) {
                $this->addError($attribute, E::PASSWORD_INCORRECT);
            }
        }
    }

    public function validateTotp($attribute) {
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
            if ($account->status === Account::STATUS_BANNED) {
                $this->addError($attribute, E::ACCOUNT_BANNED);
            }

            if ($account->status === Account::STATUS_REGISTERED) {
                $this->addError($attribute, E::ACCOUNT_NOT_ACTIVATED);
            }
        }
    }

    public function getLogin(): string {
        return $this->login;
    }

    /**
     * @CollectModelMetrics(prefix="authentication.login")
     * @return \api\components\User\AuthenticationResult|bool
     */
    public function login() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        if ($account->password_hash_strategy !== Account::PASS_HASH_STRATEGY_YII2) {
            $account->setPassword($this->password);
            $account->save();
        }

        return Yii::$app->user->createJwtAuthenticationToken($account, $this->rememberMe);
    }

}
