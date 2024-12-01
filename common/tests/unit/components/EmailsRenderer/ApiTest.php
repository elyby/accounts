<?php
declare(strict_types=1);

namespace common\tests\unit\components\EmailsRenderer;

use common\components\EmailsRenderer\Api;
use common\components\EmailsRenderer\Request\TemplateRequest;
use common\tests\unit\TestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

class ApiTest extends TestCase {

    /**
     * @var Api
     */
    private $api;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var array<array{
     *     request: \Psr\Http\Message\RequestInterface,
     *     response: \Psr\Http\Message\ResponseInterface,
     *     error: string|null,
     *     options: array<mixed>,
     * }>
     */
    private array $history = [];

    protected function setUp(): void {
        parent::setUp();

        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history), 'history');
        $client = new Client([
            'handler' => $handlerStack,
            'base_uri' => 'http://emails-renderer',
        ]);
        $this->api = new Api('http://emails-renderer');
        $this->api->setClient($client);
    }

    public function testGetTemplate() {
        $this->mockHandler->append(new Response(200, [], 'mock-response'));

        $request = new TemplateRequest('mock-name', 'mock-locale', ['find-me' => 'please']);
        $this->assertSame('mock-response', $this->api->getTemplate($request));

        /** @var \Psr\Http\Message\RequestInterface $request */
        ['request' => $request] = $this->history[0];
        $this->assertSame('http://emails-renderer/templates/mock-locale/mock-name?find-me=please', (string)$request->getUri());
    }

}
