<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\tasks\DeliveryWebHook;
use common\tests\unit\TestCase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use yii\queue\Queue;

/**
 * @covers \common\tasks\DeliveryWebHook
 */
class DeliveryWebHookTest extends TestCase {

    private array $historyContainer = [];

    /**
     * @var Response|\GuzzleHttp\Exception\GuzzleException
     */
    private $response;

    public function testCanRetry(): void {
        $task = new DeliveryWebHook();
        $this->assertFalse($task->canRetry(1, new \Exception()));
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $this->assertTrue($task->canRetry(4, new ConnectException('', $request)));
        $this->assertTrue($task->canRetry(4, new ServerException('', $request, $response)));
        $this->assertFalse($task->canRetry(5, new ConnectException('', $request)));
        $this->assertFalse($task->canRetry(5, new ServerException('', $request, $response)));
    }

    public function testExecuteSuccessDelivery(): void {
        $this->response = new Response();
        $task = $this->createMockedTask();
        $task->type = 'account.edit';
        $task->url = 'http://localhost:81/webhooks/ely';
        $task->payloads = [
            'key' => 'value',
            'another' => 'value',
        ];
        $task->execute($this->createMock(Queue::class));
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->historyContainer[0]['request'];
        $this->assertSame('http://localhost:81/webhooks/ely', (string)$request->getUri());
        $this->assertStringStartsWith('Account-Ely-Hookshot/', $request->getHeaders()['User-Agent'][0]);
        $this->assertSame('account.edit', $request->getHeaders()['X-Ely-Accounts-Event'][0]);
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaders()['Content-Type'][0]);
        $this->assertArrayNotHasKey('X-Hub-Signature', $request->getHeaders());
        $this->assertSame('key=value&another=value', (string)$request->getBody());
    }

    public function testExecuteSuccessDeliveryWithSignature(): void {
        $this->response = new Response();
        $task = $this->createMockedTask();
        $task->type = 'account.edit';
        $task->url = 'http://localhost:81/webhooks/ely';
        $task->secret = 'secret';
        $task->payloads = [
            'key' => 'value',
            'another' => 'value',
        ];
        $task->execute($this->createMock(Queue::class));
        /** @var \GuzzleHttp\Psr7\Request $request */
        $request = $this->historyContainer[0]['request'];
        $this->assertSame('http://localhost:81/webhooks/ely', (string)$request->getUri());
        $this->assertStringStartsWith('Account-Ely-Hookshot/', $request->getHeaders()['User-Agent'][0]);
        $this->assertSame('account.edit', $request->getHeaders()['X-Ely-Accounts-Event'][0]);
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaders()['Content-Type'][0]);
        $this->assertSame('sha1=3c0b1eef564b2d3a5e9c0f2a8302b1b42b3d4784', $request->getHeaders()['X-Hub-Signature'][0]);
        $this->assertSame('key=value&another=value', (string)$request->getBody());
    }

    public function testExecuteHandleClientException(): void {
        $this->response = new Response(403);
        $task = $this->createMockedTask();
        $task->type = 'account.edit';
        $task->url = 'http://localhost:81/webhooks/ely';
        $task->secret = 'secret';
        $task->payloads = [
            'key' => 'value',
            'another' => 'value',
        ];
        $task->execute($this->createMock(Queue::class));
    }

    public function testExecuteUnhandledException(): void {
        $this->expectException(ServerException::class);

        $this->response = new Response(502);
        $task = $this->createMockedTask();
        $task->type = 'account.edit';
        $task->url = 'http://localhost:81/webhooks/ely';
        $task->secret = 'secret';
        $task->payloads = [
            'key' => 'value',
            'another' => 'value',
        ];
        $task->execute($this->createMock(Queue::class));
    }

    private function createMockedTask(): DeliveryWebHook {
        $container = &$this->historyContainer;
        $response = $this->response;

        return new class($container, $response) extends DeliveryWebHook {
            private $historyContainer;

            public function __construct(
                array & $historyContainer,
                private $response,
            ) {
                $this->historyContainer = &$historyContainer;
            }

            protected function createStack(): HandlerStack {
                $stack = parent::createStack();
                $stack->setHandler(new MockHandler([$this->response]));
                $stack->push(Middleware::history($this->historyContainer));

                return $stack;
            }
        };
    }

}
