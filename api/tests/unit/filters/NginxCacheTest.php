<?php
namespace api\tests\unit\filters;

use api\filters\NginxCache;
use api\tests\unit\TestCase;
use Yii;
use yii\base\Action;
use yii\web\Controller;
use yii\web\HeaderCollection;
use yii\web\Request;

class NginxCacheTest extends TestCase {

    public function testAfterAction() {
        $this->testAfterActionInternal(3600, 3600);
        $this->testAfterActionInternal('@' . (time() + 30), '@' . (time() + 30));
        $this->testAfterActionInternal(function() {
            return 3000;
        }, 3000);
    }

    private function testAfterActionInternal($ruleConfig, $expected) {
        /** @var HeaderCollection|\PHPUnit\Framework\MockObject\MockObject $headers */
        $headers = $this->getMockBuilder(HeaderCollection::class)
            ->setMethods(['set'])
            ->getMock();

        $headers->expects($this->once())
            ->method('set')
            ->with('X-Accel-Expires', $expected);

        /** @var Request|\PHPUnit\Framework\MockObject\MockObject $request */
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getHeaders'])
            ->getMock();

        $request->expects($this->any())
            ->method('getHeaders')
            ->willReturn($headers);

        Yii::$app->set('response', $request);

        /** @var Controller|\PHPUnit\Framework\MockObject\MockObject $controller */
        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs(['mock', Yii::$app])
            ->getMock();

        $component = new NginxCache([
            'rules' => [
                'index' => $ruleConfig,
            ],
        ]);

        $component->afterAction(new Action('index', $controller), '');
    }

}
