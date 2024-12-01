<?php
declare(strict_types=1);

namespace common\tests\unit\emails;

use common\emails\exceptions\CannotRenderEmailException;
use common\emails\RendererInterface;
use common\emails\TemplateWithRenderer;
use common\tests\unit\TestCase;
use Exception;
use Yii;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

class TemplateWithRendererTest extends TestCase {

    /**
     * @var TemplateWithRenderer|\PHPUnit\Framework\MockObject\MockObject $template
     */
    private $template;

    /**
     * @var MailerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mailer;

    /**
     * @var RendererInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $renderer;

    /**
     * @var string
     */
    private $initialFromEmail;

    public function testGetLocale() {
        $this->assertSame('en', $this->template->getLocale());
        $this->template->setLocale('find me');
        $this->assertSame('find me', $this->template->getLocale());
    }

    public function testSend() {
        $this->runTestForSend();
    }

    public function testSendWithRenderError() {
        $renderException = new Exception('find me');
        try {
            $this->runTestForSend($renderException);
        } catch (CannotRenderEmailException $e) {
            // Catch exception manually to assert the previous exception
            $this->assertSame('Unable to render a template', $e->getMessage());
            $this->assertSame($renderException, $e->getPrevious());

            return;
        }

        $this->fail('no exception was thrown');
    }

    protected function _before() {
        parent::_before();
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->renderer = $this->createMock(RendererInterface::class);
        $this->template = $this->getMockForAbstractClass(TemplateWithRenderer::class, [$this->mailer, $this->renderer]);
        $this->initialFromEmail = Yii::$app->params['fromEmail'];
        Yii::$app->params['fromEmail'] = 'find-me';
    }

    protected function _after() {
        parent::_after();
        Yii::$app->params['fromEmail'] = $this->initialFromEmail;
    }

    /**
     * @throws \common\emails\exceptions\CannotRenderEmailException
     */
    private function runTestForSend($renderException = null): void {
        $renderMethodExpectation = $this->renderer->expects($this->once())->method('render')->with('mock-template', 'mock-locale', []);
        if ($renderException === null) {
            $renderMethodExpectation->willReturn('mock-template-contents');
            $times = [$this, 'once'];
        } else {
            $renderMethodExpectation->willThrowException($renderException);
            $times = [$this, 'any'];
        }

        $this->template->expects($times())->method('getSubject')->willReturn('mock-subject');
        $this->template->expects($times())->method('getTemplateName')->willReturn('mock-template');

        $message = $this->createMock(MessageInterface::class);
        $message->expects($times())->method('setTo')->with(['to@ely.by' => 'To'])->willReturnSelf();
        $message->expects($times())->method('setHtmlBody')->with('mock-template-contents')->willReturnSelf();
        $message->expects($times())->method('setFrom')->with(['find-me' => 'Ely.by Accounts'])->willReturnSelf();
        $message->expects($times())->method('setSubject')->with('mock-subject')->willReturnSelf();
        $message->expects($times())->method('send')->willReturn(true);

        $this->mailer->expects($times())->method('compose')->willReturn($message);

        $this->template->setLocale('mock-locale');
        $this->template->send(['to@ely.by' => 'To']);
    }

}
