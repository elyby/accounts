<?php
namespace api\tests\unit\modules\accounts\models;

use api\components\User\Component;
use api\components\User\Identity;
use api\modules\accounts\models\EnableTwoFactorAuthForm;
use api\tests\unit\TestCase;
use common\helpers\Error as E;
use common\models\Account;
use Yii;

class EnableTwoFactorAuthFormTest extends TestCase {

    public function testPerformAction() {
        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class . '[save]');
        $account->shouldReceive('save')->andReturn(true);
        $account->is_otp_enabled = false;
        $account->otp_secret = 'mock secret';

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class . '[terminateSessions]', [[
            'identityClass' => Identity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
        ]]);
        $component->shouldReceive('terminateSessions')->withArgs([$account, Component::KEEP_CURRENT_SESSION]);

        Yii::$app->set('user', $component);

        /** @var EnableTwoFactorAuthForm|\Mockery\MockInterface $model */
        $model = mock(EnableTwoFactorAuthForm::class . '[validate]', [$account]);
        $model->shouldReceive('validate')->andReturn(true);

        $this->assertTrue($model->performAction());
        $this->assertTrue($account->is_otp_enabled);
    }

    public function testValidateOtpDisabled() {
        $account = new Account();
        $account->is_otp_enabled = true;
        $model = new EnableTwoFactorAuthForm($account);
        $model->validateOtpDisabled('account');
        $this->assertEquals([E::OTP_ALREADY_ENABLED], $model->getErrors('account'));

        $account = new Account();
        $account->is_otp_enabled = false;
        $model = new EnableTwoFactorAuthForm($account);
        $model->validateOtpDisabled('account');
        $this->assertEmpty($model->getErrors('account'));
    }

}
