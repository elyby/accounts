<?php
namespace common\tests\unit\emails;

use common\emails\Template;
use common\tests\_support\ProtectedCaller;
use common\tests\unit\TestCase;
use Yii;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

class TemplateTest extends TestCase {
    use ProtectedCaller;

    public function testConstructor() {
        /** @var Template|\Mockery\MockInterface $template */
        $template = mock(Template::class, ['find-me'])->makePartial();
        $this->assertEquals('find-me', $template->getTo());
        $this->assertInstanceOf(MailerInterface::class, $template->getMailer());
    }

    public function testGetFrom() {
        Yii::$app->params['fromEmail'] = 'find-me';
        /** @var Template|\Mockery\MockInterface $template */
        $template = mock(Template::class)->makePartial();
        $this->assertEquals(['find-me' => 'Ely.by Accounts'], $template->getFrom());
    }

    public function testGetParams() {
        /** @var Template|\Mockery\MockInterface $template */
        $template = mock(Template::class)->makePartial();
        $this->assertEquals([], $template->getParams());
    }

    public function testCreateMessage() {
        Yii::$app->params['fromEmail'] = 'from@ely.by';
        /** @var Template|\Mockery\MockInterface $template */
        $template = mock(Template::class, [['to@ely.by' => 'To']])->makePartial();
        $template->shouldReceive('getSubject')->andReturn('mock-subject');
        /** @var MessageInterface $message */
        $message = $this->callProtected($template, 'createMessage');
        $this->assertInstanceOf(MessageInterface::class, $message);
        $this->assertEquals(['to@ely.by' => 'To'], $message->getTo());
        $this->assertEquals(['from@ely.by' => 'Ely.by Accounts'], $message->getFrom());
        $this->assertEquals('mock-subject', $message->getSubject());
    }

}
