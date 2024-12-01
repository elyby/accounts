<?php
declare(strict_types=1);

namespace common\tests\unit\models;

use Codeception\Util\ReflectionHelper;
use common\models\Account;
use common\notifications\AccountDeletedNotification;
use common\notifications\AccountEditNotification;
use common\tasks\CreateWebHooksDeliveries;
use common\tests\fixtures\MojangUsernameFixture;
use common\tests\unit\TestCase;
use const common\LATEST_RULES_VERSION;

/**
 * @covers \common\models\Account
 */
class AccountTest extends TestCase {

    public function testSetPassword() {
        $model = new Account();
        $model->setPassword('12345678');
        $this->assertNotEmpty($model->password_hash, 'hash should be set');
        $this->assertTrue($model->validatePassword('12345678'), 'validation should be passed');
        $this->assertSame(Account::PASS_HASH_STRATEGY_YII2, $model->password_hash_strategy, 'latest password hash should be used');
    }

    public function testValidatePassword() {
        // Use old hashing algorithm
        $model = new Account();
        $model->email = 'erick@skrauch.net';
        $model->password_hash = '2cfdb29eb354af970865a923335d17d9'; // 12345678
        $this->assertTrue($model->validatePassword('12345678', Account::PASS_HASH_STRATEGY_OLD_ELY), 'valid password should pass');
        $this->assertFalse($model->validatePassword('87654321', Account::PASS_HASH_STRATEGY_OLD_ELY), 'invalid password should fail');

        // Modern hash algorithm should also work
        $model = new Account();
        $model->password_hash = '$2y$04$N0q8DaHzlYILCnLYrpZfEeWKEqkPZzbawiS07GbSr/.xbRNweSLU6'; // 12345678
        $this->assertTrue($model->validatePassword('12345678', Account::PASS_HASH_STRATEGY_YII2), 'valid password should pass');
        $this->assertFalse($model->validatePassword('87654321', Account::PASS_HASH_STRATEGY_YII2), 'invalid password should fail');

        // If the second arg isn't passed model's value should be used
        $model = new Account();
        $model->email = 'erick@skrauch.net';
        $model->password_hash = '2cfdb29eb354af970865a923335d17d9'; // 12345678
        $model->password_hash_strategy = Account::PASS_HASH_STRATEGY_OLD_ELY;
        $this->assertTrue($model->validatePassword('12345678'), 'valid password should pass');
        $this->assertFalse($model->validatePassword('87654321'), 'invalid password should fail');

        // The same case for modern algorithm
        $model = new Account();
        $model->password_hash = '$2y$04$N0q8DaHzlYILCnLYrpZfEeWKEqkPZzbawiS07GbSr/.xbRNweSLU6'; // 12345678
        $model->password_hash_strategy = Account::PASS_HASH_STRATEGY_YII2;
        $this->assertTrue($model->validatePassword('12345678'), 'valid password should pass');
        $this->assertFalse($model->validatePassword('87654321'), 'invalid password should fail');
    }

    public function testHasMojangUsernameCollision() {
        $model = new Account();
        $model->username = 'ErickSkrauch';
        $this->assertFalse($model->hasMojangUsernameCollision());

        $this->tester->haveFixtures([
            'mojangUsernames' => MojangUsernameFixture::class,
        ]);

        $this->assertTrue($model->hasMojangUsernameCollision());
    }

    public function testGetProfileLink() {
        $model = new Account();
        $model->id = 123;
        $this->assertSame('http://ely.by/u123', $model->getProfileLink());
    }

    public function testIsAgreedWithActualRules() {
        $model = new Account();
        $this->assertFalse($model->isAgreedWithActualRules(), 'field is null');

        $model->rules_agreement_version = 0;
        $this->assertFalse($model->isAgreedWithActualRules(), 'actual version is greater than zero');

        $model->rules_agreement_version = LATEST_RULES_VERSION;
        $this->assertTrue($model->isAgreedWithActualRules());
    }

    public function testSetRegistrationIp() {
        $account = new Account();
        $account->setRegistrationIp('42.72.205.204');
        $this->assertSame('42.72.205.204', inet_ntop($account->registration_ip));
        $account->setRegistrationIp('2001:1620:28:1:b6f:8bca:93:a116');
        $this->assertSame('2001:1620:28:1:b6f:8bca:93:a116', inet_ntop($account->registration_ip));
        $account->setRegistrationIp(null);
        $this->assertNull($account->registration_ip);
    }

    public function testGetRegistrationIp() {
        $account = new Account();
        $account->setRegistrationIp('42.72.205.204');
        $this->assertSame('42.72.205.204', $account->getRegistrationIp());
        $account->setRegistrationIp('2001:1620:28:1:b6f:8bca:93:a116');
        $this->assertSame('2001:1620:28:1:b6f:8bca:93:a116', $account->getRegistrationIp());
        $account->setRegistrationIp(null);
        $this->assertNull($account->getRegistrationIp());
    }

    public function testAfterSaveInsertEvent() {
        $account = new Account();
        $account->afterSave(true, [
            'username' => 'old-username',
        ]);
        $this->assertNull($this->tester->grabLastQueuedJob());
    }

    public function testAfterSaveNotMeaningfulAttributes() {
        $account = new Account();
        $account->afterSave(false, [
            'updatedAt' => time(),
        ]);
        $this->assertNull($this->tester->grabLastQueuedJob());
    }

    public function testAfterSavePushEvent() {
        $changedAttributes = [
            'username' => 'old-username',
            'email' => 'old-email@ely.by',
            'uuid' => 'c3cc0121-fa87-4818-9c0e-4acb7f9a28c5',
            'status' => 10,
            'lang' => 'en',
        ];

        $account = new Account();
        $account->id = 123;
        $account->afterSave(false, $changedAttributes);
        /** @var CreateWebHooksDeliveries $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(CreateWebHooksDeliveries::class, $job);
        /** @var AccountEditNotification $notification */
        $notification = ReflectionHelper::readPrivateProperty($job, 'notification');
        $this->assertInstanceOf(AccountEditNotification::class, $notification);
        $this->assertSame(123, $notification->getPayloads()['id']);
        $this->assertSame($changedAttributes, $notification->getPayloads()['changedAttributes']);
    }

    public function testAfterDeletePushEvent() {
        $account = new Account();
        $account->id = 1;
        $account->status = Account::STATUS_REGISTERED;
        $account->created_at = time() - 60 * 60 * 24;
        $account->deleted_at = time();

        $account->afterDelete();
        $this->assertNull($this->tester->grabLastQueuedJob());

        $account->status = Account::STATUS_ACTIVE;
        $account->afterDelete();
        /** @var CreateWebHooksDeliveries $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(CreateWebHooksDeliveries::class, $job);
        /** @var AccountDeletedNotification $notification */
        $notification = ReflectionHelper::readPrivateProperty($job, 'notification');
        $this->assertInstanceOf(AccountDeletedNotification::class, $notification);
        $this->assertSame(1, $notification->getPayloads()['id']);
    }

}
