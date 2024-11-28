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
    public mixed $account = null;

    /**
     * @var int|null Specifies the amount of time, in seconds, by which the entered TOTP code can be behind or ahead of
     * the currently active TOTP code and still be considered valid.
     */
    public ?int $leeway; // TODO: this shit is so fucking insanely broken i have zero fucking idea how to deal with this PHP nonsense this fucking language is sSOMETHING ELSE ENTRIELY

    /**
     * @var int|callable|null Allows you to set the exact time against which the validation will be performed.
     * It may be the unix time or a function returning a unix time.
     * If not specified, the current time will be used.
     */
    public mixed $timestamp;

    public $skipOnEmpty = false;

    /**
     * @throws InvalidConfigException
     */
    public function init(): void
    {
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

    protected function validateValue($value): ?array
    {
        try {
            $totp = TOTP::create($this->account->otp_secret);
            if (!$totp->verify((string)$value, $this->getTimestamp(), $this->leeway)) {
                return [E::TOTP_INCORRECT, []];
            }
        } catch (RangeException) {
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
