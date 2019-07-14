<?php
namespace api\validators;

use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use RangeException;
use Yii;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

class TotpValidator extends Validator {

    /**
     * @var Account
     */
    public $account;

    /**
     * @var int|null Specifies the window in the interval of which the code will be checked.
     * Allows you to avoid the situation when the user entered the code in the last second of its existence
     * and while the request was being sent, it has changed. The value is set in +- periods, not seconds.
     */
    public $window;

    /**
     * @var int|callable|null Allows you to set the exact time against which the validation will be performed.
     * It may be the unix time or a function returning a unix time.
     * If not specified, the current time will be used.
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
        try {
            $totp = TOTP::create($this->account->otp_secret);
            if (!$totp->verify((string)$value, $this->getTimestamp(), $this->window)) {
                return [E::TOTP_INCORRECT, []];
            }
        } catch (RangeException $e) {
            return [E::TOTP_INCORRECT, []];
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
