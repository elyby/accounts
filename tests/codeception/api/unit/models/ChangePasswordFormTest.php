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

    public function testValidatePasswordAndRePasswordMatch() {
        $this->specify('error.newRePassword_does_not_match expected if passwords not match', function() {
            $account = new Account();
            $account->setPassword('12345678');
            $model = new ChangePasswordForm($account, [
                'password' => '12345678',
                'newPassword' => 'my-new-password',
                'newRePassword' => 'another-password',
            ]);
            $model->validatePasswordAndRePasswordMatch('newRePassword');
            expect($model->getErrors('newRePassword'))->equals(['error.newRePassword_does_not_match']);
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
            expect('password change time updated', $account->password_changed_at)->greaterOrEquals(time() - 2);
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
            expect('password change time updated', $account->password_changed_at)->greaterOrEquals(time() - 2);
        });
    }

}
