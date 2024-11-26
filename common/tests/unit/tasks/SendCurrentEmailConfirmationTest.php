<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\models\Account;
use common\models\AccountQuery;
use common\models\confirmations\CurrentEmailConfirmation;
use common\tasks\SendCurrentEmailConfirmation;
use common\tests\unit\TestCase;
use yii\mail\MailerInterface;
use yii\queue\Queue;

class SendCurrentEmailConfirmationTest extends TestCase {

    public function testCreateFromConfirmation() {
        $account = new Account();
        $account->username = 'mock-username';
        $account->email = 'mock@ely.by';
        $account->lang = 'id';

        $accountQuery = $this->createMock(AccountQuery::class);
        $accountQuery->method('findFor')->willReturn($account);

        $confirmation = $this->createPartialMock(CurrentEmailConfirmation::class, ['getAccount']);
        $confirmation->method('getAccount')->willReturn($accountQuery);
        $confirmation->key = 'ABCDEFG';

        $result = SendCurrentEmailConfirmation::createFromConfirmation($confirmation);
        $this->assertSame('mock-username', $result->username);
        $this->assertSame('mock@ely.by', $result->email);
        $this->assertSame('ABCDEFG', $result->code);
    }

    public function testExecute() {
        $task = new SendCurrentEmailConfirmation($this->createMock(MailerInterface::class));
        $task->username = 'mock-username';
        $task->email = 'mock@ely.by';
        $task->code = 'GFEDCBA';

        $task->execute($this->createMock(Queue::class));

        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\swiftmailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $this->assertSame(['mock@ely.by' => 'mock-username'], $email->getTo());
        $this->assertSame('Ely.by Account change E-mail confirmation', $email->getSubject());
        $children = $email->getSwiftMessage()->getChildren()[0];
        $this->assertStringContainsString('GFEDCBA', $children->getBody());
    }

}
