<?php
declare(strict_types=1);

namespace common\tests\unit\emails\templates;

use common\emails\templates\ConfirmNewEmail;
use common\tests\unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use yii\base\InvalidCallException;
use yii\mail\MailerInterface;

class ConfirmNewEmailTest extends TestCase {

    /**
     * @var ConfirmNewEmail|MockObject
     */
    private $template;

    public function testParams() {
        $this->template->setUsername('mock-username');
        $this->template->setKey('mock-key');
        $params = $this->template->getParams();
        $this->assertSame('mock-username', $params['username']);
        $this->assertSame('mock-key', $params['key']);
    }

    /**
     * @dataProvider getInvalidCallsCases
     */
    public function testInvalidCallOfParams(?string $username, ?string $key) {
        $this->expectException(InvalidCallException::class);
        $username !== null && $this->template->setUsername($username);
        $key !== null && $this->template->setKey($key);
        $this->template->getParams();
    }

    public function getInvalidCallsCases() {
        yield [null, null];
        yield ['value', null];
        yield [null, 'value'];
    }

    protected function _before() {
        parent::_before();
        /** @var MailerInterface|MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $this->template = new ConfirmNewEmail($mailer);
    }

}
