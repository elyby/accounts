<?php
namespace api\validators;

use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

class TotpValidator extends Validator {

    /**
     * @var Account
     */
    public $account;

    /**
     * @var int|null Задаёт окно, в промежуток которого будет проверяться код.
     * Позволяет избежать ситуации, когда пользователь ввёл код в последнюю секунду
     * его существования и пока шёл запрос, тот протух.
     */
    public $window;

    public $skipOnEmpty = false;

    public function init() {
        parent::init();
        if ($this->account === null) {
            $this->account = Yii::$app->user->identity;
        }

        if (!$this->account instanceof Account) {
            throw new InvalidConfigException('account should be instance of ' . Account::class);
        }

        if (empty($this->account->otp_secret)) {
            throw new InvalidConfigException('account should have not empty otp_secret');
        }
    }

    protected function validateValue($value) {
        $totp = new TOTP(null, $this->account->otp_secret);
        if (!$totp->verify((string)$value, null, $this->window)) {
            return [E::OTP_TOKEN_INCORRECT, []];
        }

        return null;
    }

}
