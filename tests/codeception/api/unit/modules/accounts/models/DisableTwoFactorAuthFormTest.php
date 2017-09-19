<?php
namespace tests\codeception\api\unit\modules\accounts\models;

use api\modules\accounts\models\DisableTwoFactorAuthForm;
use common\helpers\Error as E;
use common\models\Account;
use tests\codeception\api\unit\TestCase;

class DisableTwoFactorAuthFormTest extends TestCase {

    public function testPerformAction() {
        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class)->makePartial();
        $account->shouldReceive('save')->once()->andReturn(true);

        $account->is_otp_enabled = true;
        $account->otp_secret = 'mock secret';

        /** @var DisableTwoFactorAuthForm|\Mockery\MockInterface $model */
        $model = mock(DisableTwoFactorAuthForm::class . '[validate]', [$account]);
        $model->shouldReceive('validate')->once()->andReturn(true);

        $this->assertTrue($model->performAction());
        $this->assertNull($account->otp_secret);
        $this->assertFalse($account->is_otp_enabled);
    }

    public function testValidateOtpEnabled() {
        $account = new Account();
        $account->is_otp_enabled = false;
        $model = new DisableTwoFactorAuthForm($account);
        $model->validateOtpEnabled('account');
        $this->assertEquals([E::OTP_NOT_ENABLED], $model->getErrors('account'));

        $account = new Account();
        $account->is_otp_enabled = true;
        $model = new DisableTwoFactorAuthForm($account);
        $model->validateOtpEnabled('account');
        $this->assertEmpty($model->getErrors('account'));
    }

}
