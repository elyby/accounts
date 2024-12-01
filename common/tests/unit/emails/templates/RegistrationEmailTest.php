<?php
declare(strict_types=1);

namespace common\tests\unit\emails\templates;

use common\emails\RendererInterface;
use common\emails\templates\RegistrationEmail;
use common\emails\templates\RegistrationEmailParams;
use common\tests\unit\TestCase;
use yii\base\InvalidCallException;
use yii\mail\MailerInterface;

class RegistrationEmailTest extends TestCase {

    private RegistrationEmail $template;

    public function testParams(): void {
        $this->template->setParams(new RegistrationEmailParams('mock-username', 'mock-code', 'mock-link'));
        $params = $this->template->getParams();
        $this->assertSame('mock-username', $params['username']);
        $this->assertSame('mock-code', $params['code']);
        $this->assertSame('mock-link', $params['link']);
    }

    public function testInvalidCallOfParams(): void {
        $this->expectException(InvalidCallException::class);
        $this->template->getParams();
    }

    protected function _before(): void {
        parent::_before();
        $mailer = $this->createMock(MailerInterface::class);
        $renderer = $this->createMock(RendererInterface::class);
        $this->template = new RegistrationEmail($mailer, $renderer);
    }

}
