<?php
declare(strict_types=1);

namespace api\tests\unit\components\ReCaptcha;

use api\components\ReCaptcha\Validator;
use api\tests\unit\TestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;

class ValidatorTest extends TestCase {

    public function testValidateEmptyValue() {
        $validator = new Validator($this->createMock(ClientInterface::class));
        $this->assertFalse($validator->validate('', $error));
        $this->assertSame('error.captcha_required', $error, 'Get error.captcha_required, if passed empty value');
    }

    public function testValidateInvalidValue() {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('request')->willReturn(new Response(200, [], json_encode([
            'success' => false,
            'error-codes' => [
                'invalid-input-response', // The response parameter is invalid or malformed.
            ],
        ])));

        $validator = new Validator($mockClient);
        $this->assertFalse($validator->validate('12341234', $error));
        $this->assertSame('error.captcha_invalid', $error, 'Get error.captcha_invalid, if passed wrong value');
    }

    public function testValidateWithNetworkTroubles() {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->exactly(2))->method('request')->willReturnOnConsecutiveCalls(
            $this->throwException($this->createMock(ConnectException::class)),
            $this->returnValue(new Response(200, [], json_encode([
                'success' => true,
                'error-codes' => [
                    'invalid-input-response', // The response parameter is invalid or malformed.
                ],
            ]))),
        );
        // TODO $this->getFunctionMock(Validator::class, 'sleep')->expects($this->once());

        $validator = new Validator($mockClient);
        $this->assertTrue($validator->validate('12341234', $error));
        $this->assertNull($error);
    }

    public function testValidateWithHugeNetworkTroubles() {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->exactly(3))->method('request')->willThrowException($this->createMock(ConnectException::class));
        // TODO $this->getFunctionMock(Validator::class, 'sleep')->expects($this->exactly(2));

        $validator = new Validator($mockClient);
        $this->expectException(ConnectException::class);
        $validator->validate('12341234', $error);
    }

    public function testValidateValidValue() {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('request')->willReturn(new Response(200, [], json_encode([
            'success' => true,
        ])));
        $validator = new Validator($mockClient);
        $this->assertTrue($validator->validate('12341234', $error));
        $this->assertNull($error);
    }

}
