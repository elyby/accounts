<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha;

use api\components\Captcha\CaptchaRegistry;
use api\components\Captcha\CaptchaValidator;
use api\components\Captcha\Providers\ProviderInterface;
use api\tests\_support\Captcha\FakeCaptchaProvider;
use api\tests\unit\TestCase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use yii\base\Model;

final class CaptchaValidatorTest extends TestCase {

    /**
     * @dataProvider getValidationCases
     */
    public function testValidateAttribute(mixed $captcha, mixed $captchaType, ?string $expectedError): void {
        $validator = $this->createValidator([
            'recaptcha' => new FakeCaptchaProvider('recaptcha-public'),
            'yandex' => new FakeCaptchaProvider('yandex-public'),
        ]);
        $model = $this->createModel($captcha, $captchaType);
        $validator->validateAttribute($model, 'captcha');

        $this->assertSame($expectedError === null ? [] : [$expectedError], $model->getErrors('captcha'));
    }

    /**
     * @return iterable<string, array{mixed, mixed, string|null}>
     */
    public static function getValidationCases(): iterable {
        yield 'empty token' => ['', null, 'error.captcha_required'];
        yield 'null token' => [null, 'yandex', 'error.captcha_required'];
        yield 'non-string token' => [[FakeCaptchaProvider::VALID], null, 'error.captcha_required'];
        yield 'default provider when type is null' => [FakeCaptchaProvider::VALID, null, null];
        yield 'default provider when type is empty' => [FakeCaptchaProvider::VALID, '', null];
        yield 'explicit recaptcha' => [FakeCaptchaProvider::VALID, 'recaptcha', null];
        yield 'explicit yandex' => [FakeCaptchaProvider::VALID, 'yandex', null];
        yield 'invalid recaptcha token' => ['invalid', 'recaptcha', 'error.captcha_invalid'];
        yield 'invalid yandex token' => ['invalid', 'yandex', 'error.captcha_invalid'];
        yield 'unknown type' => [FakeCaptchaProvider::VALID, 'unknown', 'error.captcha_invalid'];
        yield 'non-string type' => [FakeCaptchaProvider::VALID, ['recaptcha'], 'error.captcha_invalid'];
    }

    public function testValidateUsesProviderSelectedByType(): void {
        $validator = $this->createValidator([
            'yandex' => new FakeCaptchaProvider('yandex-public'),
        ]);

        $model = $this->createModel(FakeCaptchaProvider::VALID, 'yandex');
        $validator->validateAttribute($model, 'captcha');
        $this->assertFalse($model->hasErrors());

        // The default (recaptcha) provider isn't registered, so the yandex one must not be used implicitly
        $model = $this->createModel(FakeCaptchaProvider::VALID, null);
        $validator->validateAttribute($model, 'captcha');
        $this->assertSame(['error.captcha_invalid'], $model->getErrors('captcha'));
    }

    public function testValidateWithNetworkTroubles(): void {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->exactly(2))->method('verify')->willReturnOnConsecutiveCalls(
            $this->throwException($this->createMock(ConnectException::class)),
            true,
        );
        $this->getFunctionMock(CaptchaValidator::class, 'sleep')->expects($this->once());

        $model = $this->createModel('token', null);
        $this->createValidator(['recaptcha' => $provider])->validateAttribute($model, 'captcha');
        $this->assertFalse($model->hasErrors());
    }

    public function testValidateWithHugeNetworkTroubles(): void {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->exactly(3))->method('verify')->willThrowException($this->createMock(ServerException::class));
        $this->getFunctionMock(CaptchaValidator::class, 'sleep')->expects($this->exactly(2));

        $this->expectException(ServerException::class);
        $this->createValidator(['recaptcha' => $provider])->validateAttribute($this->createModel('token', null), 'captcha');
    }

    /**
     * @param array<string, ProviderInterface> $providers
     */
    private function createValidator(array $providers): CaptchaValidator {
        return new CaptchaValidator(new CaptchaRegistry($providers));
    }

    private function createModel(mixed $captcha, mixed $captchaType): Model {
        return new class($captcha, $captchaType) extends Model {
            public function __construct(
                public mixed $captcha,
                public mixed $captchaType,
            ) {
                parent::__construct();
            }
        };
    }

}
