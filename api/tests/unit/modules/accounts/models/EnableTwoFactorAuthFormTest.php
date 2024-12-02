<?php
declare(strict_types=1);

namespace api\tests\unit\modules\accounts\models;

use api\components\User\Component;
use api\modules\accounts\models\EnableTwoFactorAuthForm;
use api\tests\unit\TestCase;
use common\helpers\Error as E;
use common\models\Account;
use Yii;

class EnableTwoFactorAuthFormTest extends TestCase {

    public function testPerformAction(): void {
        $account = $this->createPartialMock(Account::class, ['save']);
        $account->method('save')->willReturn(true);
        $account->is_otp_enabled = false;
        $account->otp_secret = 'mock secret';

        $component = $this->createPartialMock(Component::class, ['terminateSessions']);
        $component->method('terminateSessions')->with($account, Component::KEEP_CURRENT_SESSION);
        Yii::$app->set('user', $component);

        $model = $this->createPartialMock(EnableTwoFactorAuthForm::class, ['getAccount', 'validate']);
        $model->method('getAccount')->willReturn($account);
        $model->expects($this->once())->method('validate')->willReturn(true);

        $this->assertTrue($model->performAction());
        $this->assertTrue($account->is_otp_enabled);
    }

    public function testValidateOtpDisabled(): void {
        $account = new Account();
        $account->is_otp_enabled = true;
        $model = new EnableTwoFactorAuthForm($account);
        $model->validateOtpDisabled('account');
        $this->assertSame([E::OTP_ALREADY_ENABLED], $model->getErrors('account'));

        $account = new Account();
        $account->is_otp_enabled = false;
        $model = new EnableTwoFactorAuthForm($account);
        $model->validateOtpDisabled('account');
        $this->assertEmpty($model->getErrors('account'));
    }

}
