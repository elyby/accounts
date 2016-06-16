<?php
namespace tests\codeception\api\models\profile;

use api\components\User\Component;
use api\models\AccountIdentity;
use api\models\profile\ChangePasswordForm;
use Codeception\Specify;
use common\models\Account;
use common\models\AccountSession;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\AccountSessionFixture;
use Yii;

/**
 * @property AccountFixture $accounts
 * @property AccountSessionFixture $accountSessions
 */
class ChangePasswordFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'accountSessions' => AccountSessionFixture::class,
        ];
    }

    public function testValidatePasswordAndRePasswordMatch() {
        $this->specify('error.rePassword_does_not_match expected if passwords not match', function() {
            $account = new Account();
            $account->setPassword('12345678');
            $model = new ChangePasswordForm($account, [
                'password' => '12345678',
                'newPassword' => 'my-new-password',
                'newRePassword' => 'another-password',
            ]);
            $model->validatePasswordAndRePasswordMatch('newRePassword');
            expect($model->getErrors('newRePassword'))->equals(['error.rePassword_does_not_match']);
        });

        $this->specify('no errors expected if passwords are valid', function() {
            $account = new Account();
            $account->setPassword('12345678');
            $model = new ChangePasswordForm($account, [
                'password' => '12345678',
                'newPassword' => 'my-new-password',
                'newRePassword' => 'my-new-password',
            ]);
            $model->validatePasswordAndRePasswordMatch('newRePassword');
            expect($model->getErrors('newRePassword'))->isEmpty();
        });

        $this->specify('error.rePassword_does_not_match expected even if there are errors on other attributes', function() {
            // this is very important, because password change flow may be combined of two steps
            // therefore we need to validate password sameness before we will validate current account password
            $account = new Account();
            $account->setPassword('12345678');
            $model = new ChangePasswordForm($account, [
                'newPassword' => 'my-new-password',
                'newRePassword' => 'another-password',
            ]);
            $model->validate();
            expect($model->getErrors('newRePassword'))->equals(['error.rePassword_does_not_match']);
        });
    }

    public function testChangePassword() {
        $this->specify('successfully change password with modern hash strategy', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']['id']);
            $model = new ChangePasswordForm($account, [
                'password' => 'password_0',
                'newPassword' => 'my-new-password',
                'newRePassword' => 'my-new-password',
            ]);

            $callTime = time();
            expect('form should return true', $model->changePassword())->true();
            expect('new password should be successfully stored into account', $account->validatePassword('my-new-password'))->true();
            expect('password change time updated', $account->password_changed_at)->greaterOrEquals($callTime);
        });

        $this->specify('successfully change password with legacy hash strategy', function() {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['user-with-old-password-type']['id']);
            $model = new ChangePasswordForm($account, [
                'password' => '12345678',
                'newPassword' => 'my-new-password',
                'newRePassword' => 'my-new-password',
            ]);

            $callTime = time();
            expect($model->changePassword())->true();
            expect($account->validatePassword('my-new-password'))->true();
            expect($account->password_changed_at)->greaterOrEquals($callTime);
            expect($account->password_hash_strategy)->equals(Account::PASS_HASH_STRATEGY_YII2);
        });
    }

    public function testChangePasswordWithLogout() {
        /** @var Component|\PHPUnit_Framework_MockObject_MockObject $component */
        $component = $this->getMockBuilder(Component::class)
            ->setMethods(['getActiveSession'])
            ->setConstructorArgs([[
                'identityClass' => AccountIdentity::class,
                'enableSession' => false,
                'loginUrl' => null,
                'secret' => 'secret',
            ]])
            ->getMock();

        /** @var AccountSession $session */
        $session = AccountSession::findOne($this->accountSessions['admin2']['id']);

        $component
            ->expects($this->any())
            ->method('getActiveSession')
            ->will($this->returnValue($session));

        Yii::$app->set('user', $component);

        $this->specify('change password with removing all session, except current', function() use ($session) {
            /** @var Account $account */
            $account = Account::findOne($this->accounts['admin']['id']);

            $model = new ChangePasswordForm($account, [
                'password' => 'password_0',
                'newPassword' => 'my-new-password',
                'newRePassword' => 'my-new-password',
                'logoutAll' => true,
            ]);

            expect($model->changePassword())->true();
            /** @var AccountSession[] $sessions */
            $sessions = $account->getSessions()->all();
            expect(count($sessions))->equals(1);
            expect($sessions[0]->id)->equals($session->id);
        });
    }

}
