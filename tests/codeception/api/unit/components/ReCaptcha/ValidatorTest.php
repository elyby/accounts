<?php
namespace codeception\api\unit\components\ReCaptcha;

use api\components\ReCaptcha\Validator;
use Codeception\Specify;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use tests\codeception\api\unit\TestCase;

class ValidatorTest extends TestCase {
    use Specify;

    public function testValidateValue() {
        $this->specify('Get error.captcha_required, if passed empty value', function() {
            $validator = new Validator();
            expect($validator->validate('', $error))->false();
            expect($error)->equals('error.captcha_required');
        });

        $this->specify('Get error.captcha_invalid, if passed wrong value', function() {
            /** @var \PHPUnit_Framework_MockObject_MockObject|Validator $validator */
            $validator = $this->getMockBuilder(Validator::class)
                ->setMethods(['createClient'])
                ->getMock();

            $validator->expects($this->once())
                ->method('createClient')
                ->will($this->returnValue($this->createMockGuzzleClient([
                    'success' => false,
                    'error-codes' => [
                        'invalid-input-response', // The response parameter is invalid or malformed.
                    ],
                ])));

            expect($validator->validate('12341234', $error))->false();
            expect($error)->equals('error.captcha_invalid');
        });

        $this->specify('Get error.captcha_invalid, if passed wrong value', function() {
            /** @var \PHPUnit_Framework_MockObject_MockObject|Validator $validator */
            $validator = $this->getMockBuilder(Validator::class)
                ->setMethods(['createClient'])
                ->getMock();

            $validator->expects($this->once())
                ->method('createClient')
                ->will($this->returnValue($this->createMockGuzzleClient(['success' => true])));

            expect($validator->validate('12341234', $error))->true();
            expect($error)->null();
        });
    }

    private function createMockGuzzleClient(array $response) {
        $mock = new MockHandler([
            new Response(200, [], json_encode($response)),
        ]);
        $handler = HandlerStack::create($mock);

        return new Client(['handler' => $handler]);
    }

}
