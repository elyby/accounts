<?php
declare(strict_types=1);

namespace api\tests\unit\components\Captcha\Providers;

use api\components\Captcha\Providers\ReCaptchaEnterprise;
use api\tests\unit\TestCase;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Psr7\Response;
use yii\base\Exception;

final class ReCaptchaEnterpriseTest extends TestCase {

    public function testGetPublicParams(): void {
        $provider = $this->createProvider($this->createMock(GuzzleClientInterface::class));
        $this->assertSame(['publicKey' => 'site-key'], $provider->getPublicParams());
    }

    public function testVerifyValidToken(): void {
        $client = $this->createMock(GuzzleClientInterface::class);
        $client->expects($this->once())->method('request')->with(
            'POST',
            'https://recaptchaenterprise.googleapis.com/v1/projects/my-project/assessments',
            [
                'query' => ['key' => 'api-key'],
                'json' => [
                    'event' => [
                        'token' => 'token',
                        'siteKey' => 'site-key',
                        'userIpAddress' => '127.0.0.1',
                    ],
                ],
            ],
        )->willReturn(new Response(200, [], (string)json_encode([
            'name' => 'projects/123/assessments/456',
            'tokenProperties' => [
                'valid' => true,
                'invalidReason' => 'INVALID_REASON_UNSPECIFIED',
                'hostname' => 'account.ely.by',
                'action' => '',
            ],
        ])));

        $this->assertTrue($this->createProvider($client)->verify('token', '127.0.0.1'));
    }

    public function testVerifyInvalidToken(): void {
        $client = $this->createMock(GuzzleClientInterface::class);
        $client->method('request')->willReturn(new Response(200, [], (string)json_encode([
            'tokenProperties' => [
                'valid' => false,
                'invalidReason' => 'MALFORMED',
            ],
        ])));

        $this->assertFalse($this->createProvider($client)->verify('token', null));
    }

    public function testVerifyMalformedResponse(): void {
        $client = $this->createMock(GuzzleClientInterface::class);
        $client->method('request')->willReturn(new Response(200, [], '{}'));

        $this->expectException(Exception::class);
        $this->createProvider($client)->verify('token', null);
    }

    private function createProvider(GuzzleClientInterface $client): ReCaptchaEnterprise {
        return new ReCaptchaEnterprise('api-key', 'site-key', 'my-project', $client);
    }

}
