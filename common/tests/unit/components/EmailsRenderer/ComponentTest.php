<?php
declare(strict_types=1);

namespace common\tests\unit\components\EmailsRenderer;

use common\components\EmailsRenderer\Api;
use common\components\EmailsRenderer\Component;
use common\components\EmailsRenderer\Request\TemplateRequest;
use common\tests\unit\TestCase;

class ComponentTest extends TestCase {

    /**
     * @var Api|\PHPUnit\Framework\MockObject\MockObject
     */
    private $api;

    /**
     * @var Component
     */
    private $component;

    protected function setUp() {
        parent::setUp();

        $this->api = $this->createMock(Api::class);
        $componentParams = [
            'api' => $this->api,
            'serviceUrl' => 'http://emails-renderer',
            'basePath' => '/images/emails-templates',
        ];
        $this->component = new class($componentParams) extends Component {
            public $api;

            protected function getApi(): Api {
                return $this->api;
            }
        };
    }

    public function testRender() {
        $expectedRequest = new TemplateRequest('mock-name', 'mock-locale', [
            'find-me' => 'please',
            'assetsHost' => 'http://localhost/images/emails-templates',
        ]);

        $this->api->expects($this->once())->method('getTemplate')->with($expectedRequest)->willReturn('mock-template');

        $result = $this->component->render('mock-name', 'mock-locale', ['find-me' => 'please']);
        $this->assertSame('mock-template', $result);
    }

}
