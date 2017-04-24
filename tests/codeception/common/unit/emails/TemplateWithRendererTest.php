<?php
namespace tests\codeception\common\unit\emails;

use common\components\EmailRenderer;
use common\emails\TemplateWithRenderer;
use Ely\Email\TemplateBuilder;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\unit\TestCase;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

class TemplateWithRendererTest extends TestCase {
    use ProtectedCaller;

    public function testConstructor() {
        /** @var TemplateWithRenderer|\Mockery\MockInterface $template */
        $template = mock(TemplateWithRenderer::class, ['mock-to', 'mock-locale'])->makePartial();
        $this->assertEquals('mock-to', $template->getTo());
        $this->assertEquals('mock-locale', $template->getLocale());
        $this->assertInstanceOf(MailerInterface::class, $template->getMailer());
        $this->assertInstanceOf(EmailRenderer::class, $template->getEmailRenderer());
    }

    public function testCreateMessage() {
        /** @var TemplateBuilder|\Mockery\MockInterface $templateBuilder */
        $templateBuilder = mock(TemplateBuilder::class)->makePartial();
        $templateBuilder->shouldReceive('render')->andReturn('mock-html');

        /** @var EmailRenderer|\Mockery\MockInterface $renderer */
        $renderer = mock(EmailRenderer::class)->makePartial();
        $renderer->shouldReceive('getTemplate')->with('mock-template')->andReturn($templateBuilder);

        /** @var TemplateWithRenderer|\Mockery\MockInterface $template */
        $template = mock(TemplateWithRenderer::class, [['to@ely.by' => 'To'], 'mock-locale']);
        $template->makePartial();
        $template->shouldReceive('getEmailRenderer')->andReturn($renderer);
        $template->shouldReceive('getFrom')->andReturn(['from@ely.by' => 'From']);
        $template->shouldReceive('getSubject')->andReturn('mock-subject');
        $template->shouldReceive('getTemplateName')->andReturn('mock-template');
        /** @var \yii\swiftmailer\Message $message */
        $message = $this->callProtected($template, 'createMessage');
        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals(['to@ely.by' => 'To'], $message->getTo());
        $this->assertEquals(['from@ely.by' => 'From'], $message->getFrom());
        $this->assertEquals('mock-subject', $message->getSubject());
        $this->assertEquals('mock-html', $message->getSwiftMessage()->getBody());
    }

}
