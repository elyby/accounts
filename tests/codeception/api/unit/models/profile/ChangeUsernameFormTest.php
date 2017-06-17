<?php
namespace tests\codeception\api\models\profile;

use api\models\AccountIdentity;
use api\models\profile\ChangeUsernameForm;
use Codeception\Specify;
use common\models\Account;
use common\models\UsernameHistory;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\UsernameHistoryFixture;
use Yii;

class ChangeUsernameFormTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'history' => UsernameHistoryFixture::class,
        ];
    }

    public function setUp() {
        parent::setUp();
        Yii::$app->user->setIdentity($this->getAccount());
    }

    public function testChange() {
        $model = new ChangeUsernameForm($this->getAccount(), [
            'password' => 'password_0',
            'username' => 'my_new_nickname',
        ]);
        $this->assertTrue($model->change());
        $this->assertEquals('my_new_nickname', Account::findOne($this->getAccountId())->username);
        $this->assertInstanceOf(UsernameHistory::class, UsernameHistory::findOne(['username' => 'my_new_nickname']));
        $this->tester->canSeeAmqpMessageIsCreated('events');
    }

    public function testChangeWithoutChange() {
        $account = $this->getAccount();
        $username = $account->username;
        $model = new ChangeUsernameForm($account, [
            'password' => 'password_0',
            'username' => $username,
        ]);
        $callTime = time();
        $this->assertTrue($model->change());
        $this->assertNull(UsernameHistory::findOne([
            'AND',
            'username' => $username,
            ['>=', 'applied_in', $callTime],
        ]), 'no new UsernameHistory record, if we don\'t change username');
        $this->tester->cantSeeAmqpMessageIsCreated('events');
    }

    public function testChangeCase() {
        $newUsername = mb_strtoupper($this->tester->grabFixture('accounts', 'admin')['username']);
        $model = new ChangeUsernameForm($this->getAccount(), [
            'password' => 'password_0',
            'username' => $newUsername,
        ]);
        $this->assertTrue($model->change());
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

    private function getAccount(): AccountIdentity {
        return AccountIdentity::findOne($this->getAccountId());
    }

    private function getAccountId() {
        return $this->tester->grabFixture('accounts', 'admin')->id;
    }

}
