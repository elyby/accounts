<?php
namespace api\tests\unit\validators;

use api\validators\TotpValidator;
use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use api\tests\unit\TestCase;
use common\tests\_support\ProtectedCaller;

class TotpValidatorTest extends TestCase {
    use ProtectedCaller;

    public function testValidateValue() {
        $account = new Account();
        $account->otp_secret = 'AAAA';
        $controlTotp = TOTP::create($account->otp_secret);

        $validator = new TotpValidator(['account' => $account]);

        $result = $this->callProtected($validator, 'validateValue', 123456);
        $this->assertEquals([E::TOTP_INCORRECT, []], $result);

        $result = $this->callProtected($validator, 'validateValue', $controlTotp->now());
        $this->assertNull($result);

        $result = $this->callProtected($validator, 'validateValue', $controlTotp->at(time() - 31));
        $this->assertEquals([E::TOTP_INCORRECT, []], $result);

        $validator->window = 2;
        $result = $this->callProtected($validator, 'validateValue', $controlTotp->at(time() - 31));
        $this->assertNull($result);

        $at = time() - 400;
        $validator->timestamp = $at;
        $result = $this->callProtected($validator, 'validateValue', $controlTotp->now());
        $this->assertEquals([E::TOTP_INCORRECT, []], $result);

        $result = $this->callProtected($validator, 'validateValue', $controlTotp->at($at));
        $this->assertNull($result);

        $at = function() {
            return null;
        };
        $validator->timestamp = $at;
        $result = $this->callProtected($validator, 'validateValue', $controlTotp->now());
        $this->assertNull($result);

        $at = function() {
            return time() - 700;
        };
        $validator->timestamp = $at;
        $result = $this->callProtected($validator, 'validateValue', $controlTotp->at($at()));
        $this->assertNull($result);
    }

}
