<?php
declare(strict_types=1);

namespace common\tests\unit\emails\templates;

use common\emails\templates\ChangeEmail;
use common\tests\unit\TestCase;
use yii\base\InvalidCallException;
use yii\mail\MailerInterface;

final class ChangeEmailTest extends TestCase {

    private ChangeEmail $template;

    public function testParams(): void {
        $this->template->setKey('mock-key');
        $params = $this->template->getParams();
        $this->assertSame('mock-key', $params['key']);
    }

    public function testInvalidCallOfParams(): void {
        $this->expectException(InvalidCallException::class);
        $this->template->getParams();
    }

    protected function _before(): void {
        parent::_before();
        /** @var MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $this->template = new ChangeEmail($mailer);
    }

}
