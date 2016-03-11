<?php
namespace tests\codeception\api\models;

use api\models\ChangePasswordForm;
use Codeception\Specify;
use common\models\Account;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

/**
 * @property array $accounts
 */
class ChangePasswordFormTest extends DbTestCase {
    use Specify;

    public function fixtures() {
        return [
            'accounts' => [
                'class' => AccountFixture::class,
                'dataFile' => '@tests/codeception/common/fixtures/data/accounts.php',
            ],
        ];
    }

    public function testChangePasswordErrors() {
        /** @var Account $account */
        $account = Account::findOne($this->accounts['admin']['id']);
        $model = new ChangePasswordForm($account);
        $this->specify('expected error.{field}_required if we don\'t pass some fields', function() use ($model, $account) {
            expect('form should return false', $model->changePassword())->false();
            expect('form should contain errors', $model->getErrors())->equals([
                'password' => ['error.password_required'],
                'newPassword' => ['error.newPassword_required'],
                'newRePassword' => ['error.newRePassword_required'],
            ]);
            expect('password not changed', $account->validatePassword('password_0'))->true();
        });

        $model = new ChangePasswordForm($account, [
            'password' => 'this-is-wrong-password',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
        ]);
        $this->specify('expected error.password_incorrect if we pass invalid current account password', function() use ($model, $account) {
            expect('form should return false', $model->changePassword())->false();
            expect('form should contain errors', $model->getErrors())->equals([
                'password' => ['error.password_incorrect'],
            ]);
            expect('password not changed', $account->validatePassword('password_0'))->true();
        });

        $model = new ChangePasswordForm($account, [
            'password' => 'password_0',
            'newPassword' => 'short',
            'newRePassword' => 'short',
        ]);
        $this->specify('expected error.password_too_short if we pass short password', function() use ($model, $account) {
            expect('form should return false', $model->changePassword())->false();
            expect('form should contain errors', $model->getErrors())->equals([
                'newPassword' => ['error.password_too_short'],
            ]);
            expect('password not changed', $account->validatePassword('password_0'))->true();
        });

        $model = new ChangePasswordForm($account, [
            'password' => 'password_0',
            'newPassword' => 'first-valid-pass',
            'newRePassword' => 'another-valid-pass',
        ]);
        $this->specify('expected error.newRePassword_does_not_match if we passwords mismatch', function() use ($model, $account) {
            expect('form should return false', $model->changePassword())->false();
            expect('form should contain errors', $model->getErrors())->equals([
                'newRePassword' => ['error.newRePassword_does_not_match'],
            ]);
            expect('password not changed', $account->validatePassword('password_0'))->true();
        });
    }

    public function testChangePassword() {
        /** @var Account $account */
        $account = Account::findOne($this->accounts['admin']['id']);
        $model = new ChangePasswordForm($account, [
            'password' => 'password_0',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
        ]);
        $this->specify('successfully change password with modern hash strategy', function() use ($model, $account) {
            expect('form should return true', $model->changePassword())->true();
            expect('new password should be successfully stored into account', $account->validatePassword('my-new-password'))->true();
            expect('always use new strategy', $account->password_hash_strategy)->equals(Account::PASS_HASH_STRATEGY_YII2);
            expect('password change time updated', $account->password_changed_at)->greaterOrEquals(time());
        });

        /** @var Account $account */
        $account = Account::findOne($this->accounts['user-with-old-password-type']['id']);
        $model = new ChangePasswordForm($account, [
            'password' => '12345678',
            'newPassword' => 'my-new-password',
            'newRePassword' => 'my-new-password',
        ]);
        $this->specify('successfully change password with legacy hash strategy', function() use ($model, $account) {
            expect('form should return true', $model->changePassword())->true();
            expect('new password should be successfully stored into account', $account->validatePassword('my-new-password'))->true();
            expect('strategy should be changed to modern', $account->password_hash_strategy)->equals(Account::PASS_HASH_STRATEGY_YII2);
            expect('password change time updated', $account->password_changed_at)->greaterOrEquals(time());
        });
    }

}
