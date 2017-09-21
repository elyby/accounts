<?php
namespace tests\codeception\api\unit\modules\accounts\models;

use api\modules\accounts\models\ChangeUsernameForm;
use common\models\Account;
use common\models\UsernameHistory;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\UsernameHistoryFixture;

class ChangeUsernameFormTest extends TestCase {

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'history' => UsernameHistoryFixture::class,
        ];
    }

    public function testPerformAction() {
        $model = new ChangeUsernameForm($this->getAccount(), [
            'password' => 'password_0',
            'username' => 'my_new_nickname',
        ]);
        $this->assertTrue($model->performAction());
        $this->assertEquals('my_new_nickname', Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(UsernameHistory::class, UsernameHistory::findOne(['username' => 'my_new_nickname']));
        $this->tester->canSeeAmqpMessageIsCreated('events');
    }

    public function testPerformActionWithTheSameUsername() {
        $account = $this->getAccount();
        $username = $account->username;
        $model = new ChangeUsernameForm($account, [
            'password' => 'password_0',
            'username' => $username,
        ]);
        $callTime = time();
        $this->assertTrue($model->performAction());
        $this->assertNull(UsernameHistory::findOne([
            'AND',
            'username' => $username,
            ['>=', 'applied_in', $callTime],
        ]), 'no new UsernameHistory record, if we don\'t change username');
        $this->tester->cantSeeAmqpMessageIsCreated('events');
    }

    public function testPerformActionWithChangeCase() {
        $newUsername = mb_strtoupper($this->tester->grabFixture('accounts', 'admin')['username']);
        $model = new ChangeUsernameForm($this->getAccount(), [
            'password' => 'password_0',
            'username' => $newUsername,
        ]);
        $this->assertTrue($model->performAction());
        $this->assertEquals($newUsername, Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(
            UsernameHistory::class,
            UsernameHistory::findOne(['username' => $newUsername]),
            'username should change, if we change case of some letters'
        );
        $this->tester->canSeeAmqpMessageIsCreated('events');
    }

    public function testCreateTask() {
        $model = new ChangeUsernameForm($this->getAccount());
        $model->createEventTask(1, 'test1', 'test');
        $message = $this->tester->grabLastSentAmqpMessage('events');
        $body = json_decode($message->getBody(), true);
        $this->assertEquals(1, $body['accountId']);
        $this->assertEquals('test1', $body['newUsername']);
        $this->assertEquals('test', $body['oldUsername']);
    }

    private function getAccount(): Account {
        return $this->tester->grabFixture('accounts', 'admin');
    }

    private function getAccountId() {
        return $this->getAccount()->id;
    }

}
