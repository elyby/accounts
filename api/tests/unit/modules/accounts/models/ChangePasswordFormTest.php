<?php
namespace api\tests\unit\modules\accounts\models;

use api\components\User\Component;
use api\components\User\Identity;
use api\modules\accounts\models\ChangePasswordForm;
use api\tests\unit\TestCase;
use common\components\UserPass;
use common\helpers\Error as E;
use common\models\Account;
use Yii;
use yii\db\Transaction;

class ChangePasswordFormTest extends TestCase {

    public function testValidatePasswordAndRePasswordMatch() {
        $account = new Account();
        $account->setPassword('12345678');
        $model = new ChangePasswordForm($account, [
            'password' => '12345678',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'another-password',
        ]);
        $model->validatePasswordAndRePasswordMatch('newRePassword');
        $this->assertEquals(
            [E::NEW_RE_PASSWORD_DOES_NOT_MATCH],
            $model->getErrors('newRePassword'),
            'error.rePassword_does_not_match expected if passwords not match'
        );

        $account = new Account();
        $account->setPassword('12345678');
        $model = new ChangePasswordForm($account, [
            'password' => '12345678',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
        ]);
        $model->validatePasswordAndRePasswordMatch('newRePassword');
        $this->assertEmpty($model->getErrors('newRePassword'), 'no errors expected if passwords are valid');

        // this is very important, because password change flow may be combined of two steps
        // therefore we need to validate password sameness before we will validate current account password
        $account = new Account();
        $account->setPassword('12345678');
        $model = new ChangePasswordForm($account, [
            'newPassword' => 'my-new-password',
            'newRePassword' => 'another-password',
        ]);
        $model->validate();
        $this->assertEquals(
            [E::NEW_RE_PASSWORD_DOES_NOT_MATCH],
            $model->getErrors('newRePassword'),
            'error.rePassword_does_not_match expected even if there are errors on other attributes'
        );
        $this->assertEmpty($model->getErrors('password'));
    }

    public function testPerformAction() {
        $component = mock(Component::class . '[terminateSessions]', [[
            'identityClass' => Identity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
        ]]);
        $component->shouldNotReceive('terminateSessions');

        Yii::$app->set('user', $component);

        $transaction = mock(Transaction::class . '[commit]');
        $transaction->shouldReceive('commit');
        $connection = mock(Yii::$app->db);
        $connection->shouldReceive('beginTransaction')->andReturn($transaction);

        Yii::$app->set('db', $connection);

        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class . '[save]');
        $account->shouldReceive('save')->andReturn(true);
        $account->setPassword('password_0');

        $model = new ChangePasswordForm($account, [
            'password' => 'password_0',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
        ]);

        $callTime = time();
        $this->assertTrue($model->performAction(), 'successfully change password with modern hash strategy');
        $this->assertTrue($account->validatePassword('my-new-password'), 'new password should be successfully stored into account');
        $this->assertGreaterThanOrEqual($callTime, $account->password_changed_at, 'password change time updated');

        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class . '[save]');
        $account->shouldReceive('save')->andReturn(true);
        $account->email = 'mock@ely.by';
        $account->password_hash_strategy = Account::PASS_HASH_STRATEGY_OLD_ELY;
        $account->password_hash = UserPass::make($account->email, '12345678');

        $model = new ChangePasswordForm($account, [
            'password' => '12345678',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
        ]);

        $callTime = time();
        $this->assertTrue($model->performAction(), 'successfully change password with legacy hash strategy');
        $this->assertTrue($account->validatePassword('my-new-password'));
        $this->assertGreaterThanOrEqual($callTime, $account->password_changed_at);
        $this->assertEquals(Account::PASS_HASH_STRATEGY_YII2, $account->password_hash_strategy);
    }

    public function testPerformActionWithLogout() {
        /** @var Account|\Mockery\MockInterface $account */
        $account = mock(Account::class . '[save]');
        $account->shouldReceive('save')->andReturn(true);
        $account->setPassword('password_0');

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class . '[terminateSessions]', [[
            'identityClass' => Identity::class,
            'enableSession' => false,
            'loginUrl' => null,
            'secret' => 'secret',
        ]]);
        $component->shouldReceive('terminateSessions')->once()->withArgs([$account, Component::KEEP_CURRENT_SESSION]);

        Yii::$app->set('user', $component);

        $model = new ChangePasswordForm($account, [
            'password' => 'password_0',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
            'logoutAll' => true,
        ]);

        $this->assertTrue($model->performAction());
    }

}
