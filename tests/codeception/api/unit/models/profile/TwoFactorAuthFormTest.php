<?php
namespace tests\codeception\api\unit\models\profile;

use api\models\profile\TwoFactorAuthForm;
use common\models\Account;
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

}
