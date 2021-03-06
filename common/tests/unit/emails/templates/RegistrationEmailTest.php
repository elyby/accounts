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

    /**
     * @var RegistrationEmail()|\PHPUnit\Framework\MockObject\MockObject
     */
    private $template;

    public function testParams() {
        $this->template->setParams(new RegistrationEmailParams('mock-username', 'mock-code', 'mock-link'));
        $params = $this->template->getParams();
        $this->assertSame('mock-username', $params['username']);
        $this->assertSame('mock-code', $params['code']);
        $this->assertSame('mock-link', $params['link']);
    }

    public function testInvalidCallOfParams() {
        $this->expectException(InvalidCallException::class);
        $this->template->getParams();
    }

    protected function _before() {
        parent::_before();
        /** @var MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        /** @var RendererInterface|\PHPUnit\Framework\MockObject\MockObject $renderer */
        $renderer = $this->createMock(RendererInterface::class);
        $this->template = new RegistrationEmail($mailer, $renderer);
    }

}
