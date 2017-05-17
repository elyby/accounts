<?php
namespace codeception\api\unit\components\ReCaptcha;

use api\components\ReCaptcha\Validator;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use tests\codeception\api\unit\TestCase;

class ValidatorTest extends TestCase {

    public function testValidateValue() {
        $validator = new Validator(mock(ClientInterface::class));
        $this->assertFalse($validator->validate('', $error));
        $this->assertEquals('error.captcha_required', $error, 'Get error.captcha_required, if passed empty value');

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
        unset($error);

        $mockClient = mock(ClientInterface::class);
        $mockClient->shouldReceive('request')->andReturn(new Response(200, [], json_encode([
            'success' => true,
        ])));
        $validator = new Validator($mockClient);
        $this->assertTrue($validator->validate('12341234', $error));
        $this->assertNull($error);
    }

}
