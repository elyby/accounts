<?php
declare(strict_types=1);

namespace api\validators;

use Carbon\FactoryImmutable;
use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use Psr\Clock\ClockInterface;
use RangeException;
use yii\base\InvalidConfigException;
use yii\validators\Validator;

final class TotpValidator extends Validator {

    public ?Account $account = null;

    public $skipOnEmpty = false;

    private ClockInterface $clock;

    /**
     * @throws InvalidConfigException
     */
    public function init(): void {
        parent::init();

        if (!$this->account instanceof Account) {
            throw new InvalidConfigException('This validator must be instantiated with the account param');
        }

        if (empty($this->account->otp_secret)) {
            throw new InvalidConfigException('account should have not empty otp_secret');
        }

        $this->clock = FactoryImmutable::getDefaultInstance();
    }

    public function setClock(ClockInterface $clock): void {
        $this->clock = $clock;
    }

    protected function validateValue($value): ?array {
        try {
            // @phpstan-ignore argument.type (it is non empty, its checked in the init method)
            $totp = TOTP::create($this->account->otp_secret);
            // @phpstan-ignore argument.type,argument.type,argument.type (all types are fine, they're just not declared well)
            if (!$totp->verify((string)$value, $this->clock->now()->getTimestamp(), $totp->getPeriod() - 1)) {
                return [E::TOTP_INCORRECT, []];
            }
        } catch (RangeException) {
            return [E::TOTP_INCORRECT, []];
        }

        return null;
    }

}
