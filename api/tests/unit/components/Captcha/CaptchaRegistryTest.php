<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha;

use api\components\Captcha\CaptchaRegistry;
use api\components\Captcha\Providers\ProviderInterface;
use api\tests\unit\TestCase;
use InvalidArgumentException;

final class CaptchaRegistryTest extends TestCase {

    public function testProviders(): void {
        $recaptcha = $this->createProvider('recaptcha-public');
        $yandex = $this->createProvider('yandex-public');
        $registry = new CaptchaRegistry([
            'recaptcha' => $recaptcha,
            'yandex' => $yandex,
        ]);

        $this->assertTrue($registry->hasProvider('recaptcha'));
        $this->assertTrue($registry->hasProvider('yandex'));
        $this->assertFalse($registry->hasProvider('unknown'));
        $this->assertSame($recaptcha, $registry->getProvider('recaptcha'));
        $this->assertSame($yandex, $registry->getProvider('yandex'));
        $this->assertSame([
            'recaptcha' => ['publicKey' => 'recaptcha-public'],
            'yandex' => ['publicKey' => 'yandex-public'],
        ], $registry->getPublicParams());
    }

    public function testEmptyRegistry(): void {
        $registry = new CaptchaRegistry([]);
        $this->assertFalse($registry->hasProvider('recaptcha'));
        $this->assertSame([], $registry->getPublicParams());
    }

    public function testGetUnregisteredProvider(): void {
        $registry = new CaptchaRegistry([]);
        $this->expectException(InvalidArgumentException::class);
        $registry->getProvider('recaptcha');
    }

    private function createProvider(string $publicKey): ProviderInterface {
        $provider = $this->createMock(ProviderInterface::class);
        $provider->method('getPublicParams')->willReturn(['publicKey' => $publicKey]);

        return $provider;
    }

}
