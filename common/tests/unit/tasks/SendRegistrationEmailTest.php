<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\emails\RendererInterface;
use common\models\Account;
use common\models\AccountQuery;
use common\models\confirmations\RegistrationConfirmation;
use common\tasks\SendRegistrationEmail;
use common\tests\unit\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Yii;
use yii\queue\Queue;

class SendRegistrationEmailTest extends TestCase {

    private RendererInterface&MockObject $renderer;

    public function testCreateFromConfirmation(): void {
        $account = new Account();
        $account->username = 'mock-username';
        $account->email = 'mock@ely.by';
        $account->lang = 'ru';

        $accountQuery = $this->createMock(AccountQuery::class);
        $accountQuery->method('findFor')->willReturn($account);

        $confirmation = $this->createPartialMock(RegistrationConfirmation::class, ['getAccount']);
        $confirmation->method('getAccount')->willReturn($accountQuery);
        $confirmation->key = 'ABCDEFG';

        $result = SendRegistrationEmail::createFromConfirmation($confirmation);
        $this->assertSame('mock-username', $result->username);
        $this->assertSame('mock@ely.by', $result->email);
        $this->assertSame('ABCDEFG', $result->code);
        $this->assertSame('http://localhost/activation/ABCDEFG', $result->link);
        $this->assertSame('ru', $result->locale);
    }

    public function testExecute(): void {
        $task = new SendRegistrationEmail();
        $task->username = 'mock-username';
        $task->email = 'mock@ely.by';
        $task->code = 'GFEDCBA';
        $task->link = 'https://account.ely.by/activation/ABCDEFG';
        $task->locale = 'ru';

        $this->renderer->expects($this->once())->method('render')->with('register', 'ru', [
            'username' => 'mock-username',
            'code' => 'GFEDCBA',
            'link' => 'https://account.ely.by/activation/ABCDEFG',
        ])->willReturn('mock-template');

        $task->execute($this->createMock(Queue::class));

        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\symfonymailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $this->assertSame(['mock@ely.by' => 'mock-username'], $email->getTo());
        $this->assertSame('Ely.by Account registration', $email->getSubject());
        $this->assertSame('mock-template', $email->getSymfonyEmail()->getHtmlBody());
    }

    protected function _before() {
        parent::_before();

        $this->renderer = $this->createMock(RendererInterface::class);
        Yii::$app->set('emailsRenderer', $this->renderer);
    }

}
