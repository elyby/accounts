<?php
namespace tests\codeception\common\unit\tasks;

use common\models\Account;
use common\models\confirmations\NewEmailConfirmation;
use common\tasks\SendNewEmailConfirmation;
use tests\codeception\common\unit\TestCase;
use yii\queue\Queue;

class SendNewEmailConfirmationTest extends TestCase {

    public function testCreateFromConfirmation() {
        $account = new Account();
        $account->username = 'mock-username';
        $account->lang = 'id';

        /** @var \Mockery\Mock|NewEmailConfirmation $confirmation */
        $confirmation = mock(NewEmailConfirmation::class)->makePartial();
        $confirmation->key = 'ABCDEFG';
        $confirmation->shouldReceive('getAccount')->andReturn($account);
        $confirmation->shouldReceive('getNewEmail')->andReturn('new-email@ely.by');

        $result = SendNewEmailConfirmation::createFromConfirmation($confirmation);
        $this->assertInstanceOf(SendNewEmailConfirmation::class, $result);
        $this->assertSame('mock-username', $result->username);
        $this->assertSame('new-email@ely.by', $result->email);
        $this->assertSame('ABCDEFG', $result->code);
    }

    public function testExecute() {
        $task = new SendNewEmailConfirmation();
        $task->username = 'mock-username';
        $task->email = 'mock@ely.by';
        $task->code = 'GFEDCBA';

        $task->execute(mock(Queue::class));

        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\swiftmailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $this->assertSame(['mock@ely.by' => 'mock-username'], $email->getTo());
        $this->assertSame('Ely.by Account new E-mail confirmation', $email->getSubject());
        $children = $email->getSwiftMessage()->getChildren()[0];
        $this->assertContains('GFEDCBA', $children->getBody());
    }

}
