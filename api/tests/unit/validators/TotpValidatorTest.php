<?php
declare(strict_types=1);

namespace api\tests\unit\validators;

use api\tests\unit\TestCase;
use api\validators\TotpValidator;
use common\helpers\Error as E;
use common\models\Account;
use DateTimeImmutable;
use Lcobucci\Clock\FrozenClock;
use OTPHP\TOTP;

final class TotpValidatorTest extends TestCase {

    /**
     * The middle of a 30-second TOTP window (1700000010 is divisible by 30).
     * Using a fixed timestamp makes the test independent of the moment when it's executed.
     */
    private const int NOW = 1700000010 + 15;

    public function testValidateValue(): void {
        $account = new Account();
        $account->otp_secret = 'AAAA';
        $controlTotp = TOTP::create($account->otp_secret);

        $validator = new TotpValidator(['account' => $account]);
        $validator->setClock(new FrozenClock(new DateTimeImmutable('@' . self::NOW)));

        $this->assertFalse($validator->validate(123456, $error));
        $this->assertSame(E::TOTP_INCORRECT, $error);

        // Current window
        $error = null;
        $this->assertTrue($validator->validate($controlTotp->at(self::NOW), $error));
        $this->assertNull($error);

        // Previous window
        $error = null;
        $this->assertTrue($validator->validate($controlTotp->at(self::NOW - 30), $error));
        $this->assertNull($error);

        // Next window
        $error = null;
        $this->assertTrue($validator->validate($controlTotp->at(self::NOW + 30), $error));
        $this->assertNull($error);

        // Two windows ago is too old
        $error = null;
        $this->assertFalse($validator->validate($controlTotp->at(self::NOW - 60), $error));
        $this->assertSame(E::TOTP_INCORRECT, $error);

        // Way too old
        $error = null;
        $this->assertFalse($validator->validate($controlTotp->at(self::NOW - 400), $error));
        $this->assertSame(E::TOTP_INCORRECT, $error);
    }

}
