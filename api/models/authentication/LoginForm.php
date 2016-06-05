<?php
namespace api\models\authentication;

use api\models\AccountIdentity;
use api\models\base\ApiForm;
use api\traits\AccountFinder;
use common\models\Account;
use Yii;

/**
 * @method AccountIdentity|null getAccount()
 */
class LoginForm extends ApiForm {
    use AccountFinder;

    public $login;
    public $password;
    public $rememberMe = false;

    public function rules() {
        return [
            ['login', 'required', 'message' => 'error.login_required'],
            ['login', 'validateLogin'],

            ['password', 'required', 'when' => function(self $model) {
                return !$model->hasErrors();
            }, 'message' => 'error.password_required'],
            ['password', 'validatePassword'],

            ['login', 'validateActivity'],

            ['rememberMe', 'boolean'],
        ];
    }

    public function validateLogin($attribute) {
        if (!$this->hasErrors()) {
            if ($this->getAccount() === null) {
                $this->addError($attribute, 'error.' . $attribute . '_not_exist');
            }
        }
    }

    public function validatePassword($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account === null || !$account->validatePassword($this->password)) {
                $this->addError($attribute, 'error.' . $attribute . '_incorrect');
            }
        }
    }

    public function validateActivity($attribute) {
        if (!$this->hasErrors()) {
            $account = $this->getAccount();
            if ($account->status !== Account::STATUS_ACTIVE) {
                $this->addError($attribute, 'error.account_not_activated');
            }
        }
    }

    public function getLogin() {
        return $this->login;
    }

    /**
     * @return \api\components\User\LoginResult|bool
     */
    public function login() {
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        if ($account->password_hash_strategy === Account::PASS_HASH_STRATEGY_OLD_ELY) {
            $account->setPassword($this->password);
            $account->save();
        }

        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;

        return $component->login($account, $this->rememberMe);
    }

    protected function getAccountClassName() {
        return AccountIdentity::class;
    }

}
