<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha\Providers;

use api\components\Captcha\Providers\YandexSmartCaptcha;
use api\tests\unit\TestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use yii\base\Exception;

final class YandexSmartCaptchaTest extends TestCase {

    public function testGetPublicParams(): void {
        $provider = $this->createProvider($this->createMock(ClientInterface::class));
        $this->assertSame(['publicKey' => 'client-key'], $provider->getPublicParams());
    }

    public function testVerifyValidToken(): void {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('request')->with(
            'POST',
            'https://smartcaptcha.cloud.yandex.ru/validate',
            [
                'form_params' => [
                    'secret' => 'server-key',
                    'token' => 'token',
                    'ip' => '127.0.0.1',
                ],
            ],
        )->willReturn(new Response(200, [], (string)json_encode([
            'status' => 'ok',
            'message' => '',
            'host' => 'account.ely.by',
        ])));

        $this->assertTrue($this->createProvider($client)->verify('token', '127.0.0.1'));
    }

    public function testVerifyInvalidToken(): void {
        $client = $this->createMock(ClientInterface::class);
        $client->method('request')->willReturn(new Response(200, [], (string)json_encode([
            'status' => 'failed',
            'message' => 'Invalid or expired Token.',
        ])));

        $this->assertFalse($this->createProvider($client)->verify('token', null));
    }

    public function testVerifyMalformedResponse(): void {
        $client = $this->createMock(ClientInterface::class);
        $client->method('request')->willReturn(new Response(200, [], '{"message":""}'));

        $this->expectException(Exception::class);
        $this->createProvider($client)->verify('token', null);
    }

    private function createProvider(ClientInterface $client): YandexSmartCaptcha {
        return new YandexSmartCaptcha('server-key', 'client-key', $client);
    }

}
