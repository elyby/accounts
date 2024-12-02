<?php
declare(strict_types=1);

namespace common\tests\unit\emails;

use common\emails\exceptions\CannotSendEmailException;
use common\emails\Template;
use common\tests\unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;
use yii\mail\MailerInterface;
use yii\mail\MessageInterface;

class TemplateTest extends TestCase {

    private Template&MockObject $template;

    private MailerInterface&MockObject $mailer;

    private string $initialFromEmail;

    public function testGetters(): void {
        $this->assertSame(['find-me' => 'Ely.by Accounts'], $this->template->getFrom());
        $this->assertSame([], $this->template->getParams());
    }

    public function testSend(): void {
        $this->runTestForSend(true);
    }

    public function testNotSend(): void {
        $this->expectException(CannotSendEmailException::class);
        $this->runTestForSend(false);
    }

    protected function _before() {
        parent::_before();
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->template = $this->getMockForAbstractClass(Template::class, [$this->mailer]);
        $this->initialFromEmail = Yii::$app->params['fromEmail'];
        Yii::$app->params['fromEmail'] = 'find-me';
    }

    protected function _after(): void {
        parent::_after();
        Yii::$app->params['fromEmail'] = $this->initialFromEmail;
    }

    private function runTestForSend(bool $sendResult): void {
        $this->template->expects($this->once())->method('getSubject')->willReturn('mock-subject');
        $this->template->expects($this->once())->method('getView')->willReturn('mock-view');

        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())->method('setTo')->with(['to@ely.by' => 'To'])->willReturnSelf();
        $message->expects($this->once())->method('setFrom')->with(['find-me' => 'Ely.by Accounts'])->willReturnSelf();
        $message->expects($this->once())->method('setSubject')->with('mock-subject')->willReturnSelf();
        $message->expects($this->once())->method('send')->willReturn($sendResult);

        $this->mailer->expects($this->once())->method('compose')->with('mock-view', [])->willReturn($message);

        $this->template->send(['to@ely.by' => 'To']);
    }

}
