<?php
namespace tests\codeception\common\unit\tasks;

use common\models\Account;
use common\models\confirmations\RegistrationConfirmation;
use common\tasks\SendRegistrationEmail;
use tests\codeception\common\unit\TestCase;
use yii\queue\Queue;

class SendRegistrationEmailTest extends TestCase {

    public function testCreateFromConfirmation() {
        $account = new Account();
        $account->username = 'mock-username';
        $account->email = 'mock@ely.by';
        $account->lang = 'ru';

        /** @var \Mockery\Mock|RegistrationConfirmation $confirmation */
        $confirmation = mock(RegistrationConfirmation::class)->makePartial();
        $confirmation->key = 'ABCDEFG';
        $confirmation->shouldReceive('getAccount')->andReturn($account);

        $result = SendRegistrationEmail::createFromConfirmation($confirmation);
        $this->assertInstanceOf(SendRegistrationEmail::class, $result);
        $this->assertSame('mock-username', $result->username);
        $this->assertSame('mock@ely.by', $result->email);
        $this->assertSame('ABCDEFG', $result->code);
        $this->assertSame('http://localhost/activation/ABCDEFG', $result->link);
        $this->assertSame('ru', $result->locale);
    }

    public function testExecute() {
        $task = new SendRegistrationEmail();
        $task->username = 'mock-username';
        $task->email = 'mock@ely.by';
        $task->code = 'GFEDCBA';
        $task->link = 'https://account.ely.by/activation/ABCDEFG';
        $task->locale = 'ru';

        $task->execute(mock(Queue::class));

        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\swiftmailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $this->assertSame(['mock@ely.by' => 'mock-username'], $email->getTo());
        $this->assertSame('Ely.by Account registration', $email->getSubject());
        $body = $email->getSwiftMessage()->getBody();
        $this->assertContains('Привет, mock-username', $body);
        $this->assertContains('GFEDCBA', $body);
        $this->assertContains('https://account.ely.by/activation/ABCDEFG', $body);
    }

}
