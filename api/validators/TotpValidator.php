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
     * Значение задаётся в +- кодах, а не секундах.
     */
    public $window;

    /**
     * @var int|callable|null Позволяет задать точное время, относительно которого будет
     * выполняться проверка. Это может быть собственно время или функция, возвращающая значение.
     * Если не задано, то будет использовано текущее время.
     */
    public $timestamp;

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
        if (!$totp->verify((string)$value, $this->getTimestamp(), $this->window)) {
            return [E::OTP_TOKEN_INCORRECT, []];
        }

        return null;
    }

    private function getTimestamp(): ?int {
        $timestamp = $this->timestamp;
        if (is_callable($timestamp)) {
            $timestamp = call_user_func($this->timestamp);
        }

        if ($timestamp === null) {
            return null;
        }

        return (int)$timestamp;
    }

}
