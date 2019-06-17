<?php
declare(strict_types=1);

namespace common\tests\unit\emails\templates;

use common\emails\templates\ChangeEmail;
use common\tests\unit\TestCase;
use yii\base\InvalidCallException;
use yii\mail\MailerInterface;

class ChangeEmailTest extends TestCase {

    /**
     * @var ChangeEmail()|\PHPUnit\Framework\MockObject\MockObject
     */
    private $template;

    public function testParams() {
        $this->template->setKey('mock-key');
        $params = $this->template->getParams();
        $this->assertSame('mock-key', $params['key']);
    }

    public function testInvalidCallOfParams() {
        $this->expectException(InvalidCallException::class);
        $this->template->getParams();
    }

    protected function _before() {
        parent::_before();
        /** @var MailerInterface|\PHPUnit\Framework\MockObject\MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $this->template = new ChangeEmail($mailer);
    }

}
