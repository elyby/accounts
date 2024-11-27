<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\emails\RendererInterface;
use common\models\Account;
use common\models\AccountQuery;
use common\models\confirmations\ForgotPassword;
use common\tasks\SendPasswordRecoveryEmail;
use common\tests\unit\TestCase;
use Yii;
use yii\queue\Queue;

class SendPasswordRecoveryEmailTest extends TestCase {

    /**
     * @var RendererInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $renderer;

    public function testCreateFromConfirmation() {
        $account = new Account();
        $account->username = 'mock-username';
        $account->email = 'mock@ely.by';
        $account->lang = 'id';

        $accountQuery = $this->createMock(AccountQuery::class);
        $accountQuery->method('findFor')->willReturn($account);

        $confirmation = $this->createPartialMock(ForgotPassword::class, ['getAccount']);
        $confirmation->method('getAccount')->willReturn($accountQuery);
        $confirmation->key = 'ABCDEFG';

        $result = SendPasswordRecoveryEmail::createFromConfirmation($confirmation);
        $this->assertSame('mock-username', $result->username);
        $this->assertSame('mock@ely.by', $result->email);
        $this->assertSame('ABCDEFG', $result->code);
        $this->assertSame('http://localhost/recover-password/ABCDEFG', $result->link);
        $this->assertSame('id', $result->locale);
    }

    public function testExecute() {
        $task = new SendPasswordRecoveryEmail();
        $task->username = 'mock-username';
        $task->email = 'mock@ely.by';
        $task->code = 'GFEDCBA';
        $task->link = 'https://account.ely.by/recover-password/ABCDEFG';
        $task->locale = 'ru';

        $this->renderer->expects($this->once())->method('render')->with('forgotPassword', 'ru', [
            'username' => 'mock-username',
            'code' => 'GFEDCBA',
            'link' => 'https://account.ely.by/recover-password/ABCDEFG',
        ])->willReturn('mock-template');

        $task->execute($this->createMock(Queue::class));

        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\symfonymailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $this->assertSame(['mock@ely.by' => 'mock-username'], $email->getTo());
        $this->assertSame('Ely.by Account forgot password', $email->getSubject());
        $this->assertSame('mock-template', $email->getSymfonyEmail()->getHtmlBody());
    }

    protected function _before() {
        parent::_before();

        $this->renderer = $this->createMock(RendererInterface::class);
        Yii::$app->set('emailsRenderer', $this->renderer);
    }

}
