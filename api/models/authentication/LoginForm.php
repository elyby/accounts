<?php
declare(strict_types=1);

namespace api\models\authentication;

use api\models\base\ApiForm;
use api\validators\TotpValidator;
use common\helpers\Error as E;
use common\models\Account;
use common\models\AccountSession;
use Webmozart\Assert\Assert;
use Yii;

class LoginForm extends ApiForm {

    /**
     * @var string
     */
    public string $login;

    /**
     * @var string
     */
    public string $password;

    /**
     * @var string|null
     */
    public ?string $totp = null;

    /**
     * @var bool
     */
    public bool $rememberMe = false;

    public function rules(): array {
        return [
            ['login', 'required', 'message' => E::LOGIN_REQUIRED],
            ['login', 'validateLogin'],

            ['password', 'required', 'when' => function(self $model): bool {
                return !$model->hasErrors();
            }, 'message' => E::PASSWORD_REQUIRED],
            ['password', 'validatePassword'],

            ['totp', 'required', 'when' => function(self $model): bool {
                return !$model->hasErrors() && $model->getAccount()->is_otp_enabled;
            }, 'message' => E::TOTP_REQUIRED],
            ['totp', 'validateTotp'],

            ['login', 'validateActivity'],

            ['rememberMe', 'boolean'],
        ];
    }

    public function validateLogin(string $attribute): void {
        if (!$this->hasErrors() && $this->getAccount() === null) {
            $this->addError($attribute, E::LOGIN_NOT_EXIST);
        }
    }

    public function validatePassword(string $attribute): void {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account === null || !$account->validatePassword($this->password)) {
                $this->addError($attribute, E::PASSWORD_INCORRECT);
            }
        }
    }

    public function validateTotp(string $attribute): void {
        if ($this->hasErrors()) {
            return;
        }

        /** @var Account $account */
        $account = $this->getAccount();
        if (!$account->is_otp_enabled) {
            return;
        }

        $validator = new TotpValidator(['account' => $account]);
        $validator->validateAttribute($this, $attribute);
    }

    public function validateActivity(string $attribute): void {
        if (!$this->hasErrors()) {
            /** @var Account $account */
            $account = $this->getAccount();
            if ($account->status === Account::STATUS_BANNED) {
                $this->addError($attribute, E::ACCOUNT_BANNED);
            }

            if ($account->status === Account::STATUS_REGISTERED) {
                $this->addError($attribute, E::ACCOUNT_NOT_ACTIVATED);
            }
        }
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function getAccount(): ?Account {
        return Account::find()->andWhereLogin($this->login)->one();
    }

    public function login(): ?AuthenticationResult {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();

        /** @var Account $account */
        $account = $this->getAccount();
        if ($account->password_hash_strategy !== Account::PASS_HASH_STRATEGY_YII2) {
            $account->setPassword($this->password);
            Assert::true($account->save(), 'Unable to upgrade user\'s password');
        }

        $session = null;
        if ($this->rememberMe) {
            $session = new AccountSession();
            $session->account_id = $account->id;
            $session->setIp(Yii::$app->request->userIP);
            $session->generateRefreshToken();
            Assert::true($session->save(), 'Cannot save account session model');
        }

        $token = Yii::$app->tokensFactory->createForWebAccount($account, $session);

        $transaction->commit();

        return new AuthenticationResult($token, $session?->refresh_token);
    }

}
