<?php
declare(strict_types=1);

namespace api\validators;

use api\rbac\Permissions as P;
use common\helpers\Error as E;
use common\models\Account;
use yii\di\Instance;
use yii\validators\Validator;
use yii\web\User;

class PasswordRequiredValidator extends Validator {

    public Account $account;

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
        $this->user = Instance::ensure($this->user, User::class);
    }

    protected function validateValue($value): ?array {
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
