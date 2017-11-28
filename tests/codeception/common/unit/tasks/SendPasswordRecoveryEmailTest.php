<?php
namespace tests\codeception\common\unit\tasks;

use common\models\Account;
use common\models\confirmations\ForgotPassword;
use common\tasks\SendPasswordRecoveryEmail;
use tests\codeception\common\unit\TestCase;
use yii\queue\Queue;

class SendPasswordRecoveryEmailTest extends TestCase {

    public function testCreateFromConfirmation() {
        $account = new Account();
        $account->username = 'mock-username';
        $account->email = 'mock@ely.by';
        $account->lang = 'id';

        /** @var \Mockery\Mock|ForgotPassword $confirmation */
        $confirmation = mock(ForgotPassword::class)->makePartial();
        $confirmation->key = 'ABCDEFG';
        $confirmation->shouldReceive('getAccount')->andReturn($account);

        $result = SendPasswordRecoveryEmail::createFromConfirmation($confirmation);
        $this->assertInstanceOf(SendPasswordRecoveryEmail::class, $result);
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

        $task->execute(mock(Queue::class));

        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\swiftmailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $this->assertSame(['mock@ely.by' => 'mock-username'], $email->getTo());
        $this->assertSame('Ely.by Account forgot password', $email->getSubject());
        $body = $email->getSwiftMessage()->getBody();
        $this->assertContains('Привет, mock-username', $body);
        $this->assertContains('GFEDCBA', $body);
        $this->assertContains('https://account.ely.by/recover-password/ABCDEFG', $body);
    }

}
