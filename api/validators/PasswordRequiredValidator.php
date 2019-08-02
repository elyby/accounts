<?php
namespace api\validators;

use api\rbac\Permissions as P;
use common\helpers\Error as E;
use common\models\Account;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use yii\validators\Validator;
use yii\web\User;

class PasswordRequiredValidator extends Validator {

    /**
     * @var Account
     */
    public $account;

    /**
     * @inheritdoc
     */
    public $skipOnEmpty = false;

    /**
     * @var User|string
     */
    public $user = 'user';

    public function init() {
        parent::init();
        if (!$this->account instanceof Account) {
            throw new InvalidConfigException('account should be instance of ' . Account::class);
        }

        $this->user = Instance::ensure($this->user, User::class);
    }

    protected function validateValue($value) {
        if ($this->user->can(P::ESCAPE_IDENTITY_VERIFICATION)) {
            return null;
        }

        if (empty($value)) {
            return [E::PASSWORD_REQUIRED, []];
        }

        if ($this->account->validatePassword($value) === false) {
            return [E::PASSWORD_INCORRECT, []];
        }

        return null;
    }

}
