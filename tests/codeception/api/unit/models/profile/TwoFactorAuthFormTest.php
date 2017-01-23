<?php
namespace tests\codeception\api\unit\models\profile;

use api\models\profile\TwoFactorAuthForm;
use common\helpers\Error as E;
use common\models\Account;
use OTPHP\TOTP;
use tests\codeception\api\unit\TestCase;

class TwoFactorAuthFormTest extends TestCase {

    public function testGetCredentials() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $account->email = 'mock@email.com';
        $account->otp_secret = null;

        /** @var TwoFactorAuthForm|\PHPUnit_Framework_MockObject_MockObject $model */
        $model = $this->getMockBuilder(TwoFactorAuthForm::class)
            ->setConstructorArgs([$account])
            ->setMethods(['drawQrCode'])
            ->getMock();

        $model->expects($this->once())
            ->method('drawQrCode')
            ->willReturn('this is qr code, trust me');

        $result = $model->getCredentials();
        $this->assertTrue(is_array($result));
        $this->assertArrayHasKey('qr', $result);
        $this->assertArrayHasKey('uri', $result);
        $this->assertArrayHasKey('secret', $result);
        $this->assertNotNull($account->otp_secret);
        $this->assertEquals($account->otp_secret, $result['secret']);
        $this->assertEquals(base64_encode('this is qr code, trust me'), $result['qr']);

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->never())
            ->method('save');

        $account->email = 'mock@email.com';
        $account->otp_secret = 'some valid totp secret value';

        /** @var TwoFactorAuthForm|\PHPUnit_Framework_MockObject_MockObject $model */
        $model = $this->getMockBuilder(TwoFactorAuthForm::class)
            ->setConstructorArgs([$account])
            ->setMethods(['drawQrCode'])
            ->getMock();

        $model->expects($this->once())
            ->method('drawQrCode')
            ->willReturn('this is qr code, trust me');

        $result = $model->getCredentials();
        $this->assertEquals('some valid totp secret value', $result['secret']);
    }

    public function testActivate() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $account->is_otp_enabled = false;
        $account->otp_secret = 'mock secret';

        /** @var TwoFactorAuthForm|\PHPUnit_Framework_MockObject_MockObject $model */
        $model = $this->getMockBuilder(TwoFactorAuthForm::class)
            ->setMethods(['validate'])
            ->setConstructorArgs([$account, ['scenario' => TwoFactorAuthForm::SCENARIO_ACTIVATE]])
            ->getMock();

        $model->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        $this->assertTrue($model->activate());
        $this->assertTrue($account->is_otp_enabled);
    }

    public function testDisable() {
        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getMockBuilder(Account::class)
            ->setMethods(['save'])
            ->getMock();

        $account->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $account->is_otp_enabled = true;
        $account->otp_secret = 'mock secret';

        /** @var TwoFactorAuthForm|\PHPUnit_Framework_MockObject_MockObject $model */
        $model = $this->getMockBuilder(TwoFactorAuthForm::class)
            ->setMethods(['validate'])
            ->setConstructorArgs([$account, ['scenario' => TwoFactorAuthForm::SCENARIO_DISABLE]])
            ->getMock();

        $model->expects($this->once())
            ->method('validate')
            ->willReturn(true);

        $this->assertTrue($model->disable());
        $this->assertNull($account->otp_secret);
        $this->assertFalse($account->is_otp_enabled);
    }

    public function testValidateOtpDisabled() {
        $account = new Account();
        $account->is_otp_enabled = true;
        $model = new TwoFactorAuthForm($account);
        $model->validateOtpDisabled('account');
        $this->assertEquals([E::OTP_ALREADY_ENABLED], $model->getErrors('account'));

        $account = new Account();
        $account->is_otp_enabled = false;
        $model = new TwoFactorAuthForm($account);
        $model->validateOtpDisabled('account');
        $this->assertEmpty($model->getErrors('account'));
    }

    public function testValidateOtpEnabled() {
        $account = new Account();
        $account->is_otp_enabled = false;
        $model = new TwoFactorAuthForm($account);
        $model->validateOtpEnabled('account');
        $this->assertEquals([E::OTP_NOT_ENABLED], $model->getErrors('account'));

        $account = new Account();
        $account->is_otp_enabled = true;
        $model = new TwoFactorAuthForm($account);
        $model->validateOtpEnabled('account');
        $this->assertEmpty($model->getErrors('account'));
    }

    public function testGetTotp() {
        $account = new Account();
        $account->otp_secret = 'mock secret';
        $account->email = 'check@this.email';

        $model = new TwoFactorAuthForm($account);
        $totp = $model->getTotp();
        $this->assertInstanceOf(TOTP::class, $totp);
        $this->assertEquals('check@this.email', $totp->getLabel());
        $this->assertEquals('mock secret', $totp->getSecret());
        $this->assertEquals('Ely.by', $totp->getIssuer());
    }

}
