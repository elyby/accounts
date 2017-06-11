<?php
namespace codeception\api\unit\components\ReCaptcha;

use api\components\ReCaptcha\Validator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use phpmock\mockery\PHPMockery;
use ReflectionClass;
use tests\codeception\api\unit\TestCase;

class ValidatorTest extends TestCase {

    public function testValidateEmptyValue() {
        $validator = new Validator(mock(ClientInterface::class));
        $this->assertFalse($validator->validate('', $error));
        $this->assertEquals('error.captcha_required', $error, 'Get error.captcha_required, if passed empty value');
    }

    public function testValidateInvalidValue() {
        $mockClient = mock(ClientInterface::class);
        $mockClient->shouldReceive('request')->andReturn(new Response(200, [], json_encode([
            'success' => false,
            'error-codes' => [
                'invalid-input-response', // The response parameter is invalid or malformed.
            ],
        ])));

        $validator = new Validator($mockClient);
        $this->assertFalse($validator->validate('12341234', $error));
        $this->assertEquals('error.captcha_invalid', $error, 'Get error.captcha_invalid, if passed wrong value');
    }

    public function testValidateWithNetworkTroubles() {
        $mockClient = mock(ClientInterface::class);
        $mockClient->shouldReceive('request')->andThrow(mock(ConnectException::class))->once();
        $mockClient->shouldReceive('request')->andReturn(new Response(200, [], json_encode([
            'success' => true,
            'error-codes' => [
                'invalid-input-response', // The response parameter is invalid or malformed.
            ],
        ])))->once();
        PHPMockery::mock($this->getClassNamespace(Validator::class), 'sleep')->once();

        $validator = new Validator($mockClient);
        $this->assertTrue($validator->validate('12341234', $error));
        $this->assertNull($error);
    }

    public function testValidateWithHugeNetworkTroubles() {
        $mockClient = mock(ClientInterface::class);
        $mockClient->shouldReceive('request')->andThrow(mock(ConnectException::class))->times(3);
        PHPMockery::mock($this->getClassNamespace(Validator::class), 'sleep')->times(2);

        $validator = new Validator($mockClient);
        $this->expectException(ConnectException::class);
        $validator->validate('12341234', $error);
    }

    public function testValidateValidValue() {
        $mockClient = mock(ClientInterface::class);
        $mockClient->shouldReceive('request')->andReturn(new Response(200, [], json_encode([
            'success' => true,
        ])));
        $validator = new Validator($mockClient);
        $this->assertTrue($validator->validate('12341234', $error));
        $this->assertNull($error);
    }

    private function getClassNamespace(string $className): string {
        return (new ReflectionClass($className))->getNamespaceName();
    }

}
