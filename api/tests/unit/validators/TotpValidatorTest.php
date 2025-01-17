<?php
declare(strict_types=1);

namespace api\tests\unit\validators;

use api\tests\unit\TestCase;
use api\validators\TotpValidator;
use Carbon\CarbonImmutable;
use common\helpers\Error as E;
use common\models\Account;
use Lcobucci\Clock\FrozenClock;
use OTPHP\TOTP;

final class TotpValidatorTest extends TestCase {

    public function testValidateValue(): void {
        $account = new Account();
        $account->otp_secret = 'AAAA';
        $controlTotp = TOTP::create($account->otp_secret);

        $validator = new TotpValidator(['account' => $account]);

        $this->assertFalse($validator->validate(123456, $error));
        $this->assertSame(E::TOTP_INCORRECT, $error);

        $error = null;

        $this->assertTrue($validator->validate($controlTotp->now(), $error));
        $this->assertNull($error);

        $error = null;

        // @phpstan-ignore argument.type
        $this->assertTrue($validator->validate($controlTotp->at(time() - 31), $error));
        $this->assertNull($error);

        $error = null;

        $validator->setClock(new FrozenClock(CarbonImmutable::now()->subSeconds(400)));
        $this->assertFalse($validator->validate($controlTotp->now(), $error));
        $this->assertSame(E::TOTP_INCORRECT, $error);
    }

}
