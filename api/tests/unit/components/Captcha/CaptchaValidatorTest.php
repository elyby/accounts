<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha;

use api\components\Captcha\CaptchaRegistry;
use api\components\Captcha\CaptchaValidator;
use api\components\Captcha\Events\CaptchaEvent;
use api\components\Captcha\Events\CaptchaResultEvent;
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

    /**
     * @dataProvider getEventsCases
     *
     * @param list<string> $expectedEvents
     */
    public function testValidateTriggersEvents(mixed $captcha, mixed $captchaType, array $expectedEvents): void {
        $validator = $this->createValidator([
            'recaptcha' => new FakeCaptchaProvider('recaptcha-public'),
            'yandex' => new FakeCaptchaProvider('yandex-public'),
        ]);
        $events = $this->collectEvents($validator);
        $validator->validateAttribute($this->createModel($captcha, $captchaType), 'captcha');

        $this->assertSame($expectedEvents, $events->list);
    }

    /**
     * @return iterable<string, array{mixed, mixed, list<string>}>
     */
    public static function getEventsCases(): iterable {
        yield 'valid default token' => [FakeCaptchaProvider::VALID, null, [
            'beforeVerify:recaptcha',
            'afterVerify:recaptcha:valid',
        ]];
        yield 'invalid yandex token' => ['invalid', 'yandex', [
            'beforeVerify:yandex',
            'afterVerify:yandex:invalid',
        ]];
        yield 'empty token' => ['', 'yandex', []];
        yield 'unknown type' => [FakeCaptchaProvider::VALID, 'unknown', []];
    }

    public function testValidateWithNetworkTroubles(): void {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->exactly(2))->method('verify')->willReturnOnConsecutiveCalls(
            $this->throwException($this->createMock(ConnectException::class)),
            true,
        );
        $this->getFunctionMock(CaptchaValidator::class, 'sleep')->expects($this->once());

        $model = $this->createModel('token', null);
        $validator = $this->createValidator(['recaptcha' => $provider]);
        $events = $this->collectEvents($validator);
        $validator->validateAttribute($model, 'captcha');
        $this->assertFalse($model->hasErrors());
        $this->assertSame([
            'beforeVerify:recaptcha',
            'afterVerify:recaptcha:valid',
        ], $events->list);
    }

    public function testValidateWithHugeNetworkTroubles(): void {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->expects($this->exactly(3))->method('verify')->willThrowException($this->createMock(ServerException::class));
        $this->getFunctionMock(CaptchaValidator::class, 'sleep')->expects($this->exactly(2));

        $validator = $this->createValidator(['recaptcha' => $provider]);
        $events = $this->collectEvents($validator);
        try {
            $validator->validateAttribute($this->createModel('token', null), 'captcha');
            $this->fail('Expected exception wasn\'t thrown');
        } catch (ServerException) {
            // The after verify event mustn't be triggered when the verification has failed
            $this->assertSame(['beforeVerify:recaptcha'], $events->list);
        }
    }

    /**
     * @return object{list: list<string>}
     */
    private function collectEvents(CaptchaValidator $validator): object {
        $events = new class {
            /** @var list<string> */
            public array $list = [];
        };
        $handler = function(CaptchaEvent $event) use ($events): void {
            $item = $event->name . ':' . $event->provider;
            if ($event->name === CaptchaValidator::EVENT_AFTER_VERIFY) {
                $this->assertInstanceOf(CaptchaResultEvent::class, $event);
                $this->assertGreaterThanOrEqual(0, $event->duration->totalMicroseconds);
                $item .= ':' . ($event->isValid ? 'valid' : 'invalid');
            }

            $events->list[] = $item;
        };
        $validator->on(CaptchaValidator::EVENT_BEFORE_VERIFY, $handler);
        $validator->on(CaptchaValidator::EVENT_AFTER_VERIFY, $handler);

        return $events;
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
