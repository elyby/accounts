<?php
namespace api\validators;

use common\helpers\Error as E;
use common\models\Account;
use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

class PasswordRequiredValidator extends Validator {

    /**
     * @var Account
     */
    public $account;

    /**
     * @inheritdoc
     */
    public $skipOnEmpty = false;

    public function init() {
        parent::init();
        if ($this->account === null) {
            $this->account = Yii::$app->user->identity;
        }

        if (!$this->account instanceof Account) {
            throw new InvalidConfigException('account should be instance of ' . Account::class);
        }
    }

    protected function validateValue($value) {
        if (empty($value)) {
            return [E::PASSWORD_REQUIRED, []];
        }

        if ($this->account->validatePassword($value) === false) {
            return [E::PASSWORD_INCORRECT, []];
        }

        return null;
    }

}
