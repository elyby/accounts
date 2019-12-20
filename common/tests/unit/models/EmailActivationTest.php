<?php
declare(strict_types=1);

namespace common\tests\unit\models;

use Carbon\Carbon;
use common\models\confirmations;
use common\models\EmailActivation;
use common\tests\fixtures\EmailActivationFixture;
use common\tests\unit\TestCase;
use DateInterval;

class EmailActivationTest extends TestCase {

    public function _fixtures(): array {
        return [
            'emailActivations' => EmailActivationFixture::class,
        ];
    }

    /**
     * @dataProvider getInstantiateTestCases
     */
    public function testInstantiate(int $type, string $expectedClassType) {
        $this->assertInstanceOf($expectedClassType, EmailActivation::findOne(['type' => $type]));
    }

    public function getInstantiateTestCases() {
        yield [EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION, confirmations\RegistrationConfirmation::class];
        yield [EmailActivation::TYPE_FORGOT_PASSWORD_KEY, confirmations\ForgotPassword::class];
        yield [EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION, confirmations\CurrentEmailConfirmation::class];
        yield [EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION, confirmations\NewEmailConfirmation::class];
    }

    public function testCanResend() {
        $model = $this->createPartialMock(EmailActivation::class, ['getResendTimeout']);
        $model->method('getResendTimeout')->willReturn(new DateInterval('PT10M'));

        $model->created_at = time();
        $this->assertFalse($model->canResend());
        $this->assertEqualsWithDelta(Carbon::now()->addMinutes(10), $model->canResendAt(), 3);

        $model->created_at = time() - 60 * 10 - 1;
        $this->assertTrue($model->canResend());
        $this->assertEqualsWithDelta(Carbon::now()->subSecond(), $model->canResendAt(), 3);
    }

    public function testCanResendWithNullTimeout() {
        $model = $this->createPartialMock(EmailActivation::class, ['getResendTimeout']);
        $model->method('getResendTimeout')->willReturn(null);

        $model->created_at = time();
        $this->assertTrue($model->canResend());
        $this->assertEqualsWithDelta(Carbon::now(), $model->canResendAt(), 3);
    }

    public function testIsStale() {
        $model = $this->createPartialMock(EmailActivation::class, ['getExpireDuration']);
        $model->method('getExpireDuration')->willReturn(new DateInterval('PT10M'));

        $model->created_at = time();
        $this->assertFalse($model->isStale());

        $model->created_at = time() - 60 * 10 - 1;
        $this->assertTrue($model->isStale());
    }

    public function testIsStaleWithNullDuration() {
        $model = $this->createPartialMock(EmailActivation::class, ['getExpireDuration']);
        $model->method('getExpireDuration')->willReturn(null);

        $model->created_at = time();
        $this->assertFalse($model->isStale());
    }

}
