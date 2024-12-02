<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\modules\accounts\models\DisableTwoFactorAuthForm;
use api\tests\unit\TestCase;
use common\helpers\Error as E;
use common\models\Account;

class DisableTwoFactorAuthFormTest extends TestCase {

    public function testPerformAction(): void {
        $account = $this->createPartialMock(Account::class, ['save']);
        $account->expects($this->once())->method('save')->willReturn(true);

        $account->is_otp_enabled = true;
        $account->otp_secret = 'mock secret';

        $model = $this->createPartialMock(DisableTwoFactorAuthForm::class, ['getAccount', 'validate']);
        $model->method('getAccount')->willReturn($account);
        $model->expects($this->once())->method('validate')->willReturn(true);

        $this->assertTrue($model->performAction());
        $this->assertNull($account->otp_secret);
        $this->assertFalse($account->is_otp_enabled);
    }

    public function testValidateOtpEnabled(): void {
        $account = new Account();
        $account->is_otp_enabled = false;
        $model = new DisableTwoFactorAuthForm($account);
        $model->validateOtpEnabled('account');
        $this->assertSame([E::OTP_NOT_ENABLED], $model->getErrors('account'));

        $account = new Account();
        $account->is_otp_enabled = true;
        $model = new DisableTwoFactorAuthForm($account);
        $model->validateOtpEnabled('account');
        $this->assertEmpty($model->getErrors('account'));
    }

}
