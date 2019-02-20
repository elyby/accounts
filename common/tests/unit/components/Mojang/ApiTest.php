<?php
namespace common\tests\unit\components\Mojang;

use common\components\Mojang\Api;
use common\components\Mojang\response\UsernameToUUIDResponse;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use common\tests\unit\TestCase;
use Yii;

class ApiTest extends TestCase {

    /**
     * @var MockHandler
     */
    private $handler;

    public function _before() {
        parent::_before();

        $this->handler = new MockHandler();
        $handler = HandlerStack::create($this->handler);
        Yii::$app->set('guzzle', new GuzzleClient([
            'handler' => $handler,
        ]));
    }

    public function testUsernameToUUID() {
        $this->handler->append(new Response(200, [], '{"id": "7125ba8b1c864508b92bb5c042ccfe2b","name": "KrisJelbring"}'));
        $response = (new Api())->usernameToUUID('KrisJelbring');
        $this->assertInstanceOf(UsernameToUUIDResponse::class, $response);
        $this->assertEquals('7125ba8b1c864508b92bb5c042ccfe2b', $response->id);
        $this->assertEquals('KrisJelbring', $response->name);
    }

    /**
     * @expectedException \common\components\Mojang\exceptions\NoContentException
     */
    public function testUsernameToUUIDNoContent() {
        $this->handler->append(new Response(204));
        (new Api())->usernameToUUID('some-non-exists-user');
    }

    /**
     * @expectedException \GuzzleHttp\Exception\RequestException
     */
    public function testUsernameToUUID404() {
        $this->handler->append(new Response(404, [], '{"error":"Not Found","errorMessage":"The server has not found anything matching the request URI"}'));
        (new Api())->usernameToUUID('#hashedNickname');
    }

}
