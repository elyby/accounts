<?php
namespace tests\codeception\api\modules\login\models;

use api\modules\login\models\AuthenticationForm;
use Codeception\Specify;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

class AuthenticationFormTest extends DbTestCase {

    use Specify;

    protected function tearDown() {
        Yii::$app->user->logout();
        parent::tearDown();
    }

    public function testValidateEmail() {
        $model = new AuthenticationForm();
        $this->specify('error.email_required expected if email is not set', function() use ($model) {
            $model->validate(['email']);
            expect($model->getErrors('email'))->equals(['error.email_required']);
        });

        $this->specify('error.email_invalid expected if email not correct', function() use ($model) {
            $model->email = 'wrong-email-string';
            $model->validate(['email']);
            expect($model->getErrors('email'))->equals(['error.email_invalid']);

            $model->email = 'wrong@email';
            $model->validate(['email']);
            expect($model->getErrors('email'))->equals(['error.email_invalid']);
        });

        $this->specify('error.email_not_exist expected if email not exists in database', function() use ($model) {
            $model->email = 'not-exist@user.com';
            $model->validate(['email']);
            expect($model->getErrors('email'))->equals(['error.email_not_exist']);
        });

        $this->specify('no errors if email is correct and exists in database', function() use ($model) {
            $model->email = 'admin@ely.by';
            $model->validate(['email']);
            expect($model->getErrors('email'))->isEmpty();
        });
    }

    public function testValidatePassword() {
        $model = new AuthenticationForm();
        $this->specify('error.password_required expected if password is not set', function() use ($model) {
            $model->validate(['password']);
            expect($model->getErrors('password'))->equals(['error.password_required']);
        });

        $this->specify('error.password_incorrect expected if password not correct for passed email', function() use ($model) {
            $model->email = 'non-exist@valid.mail';
            $model->password = 'wrong-password';
            $model->validate(['password']);
            expect('if email incorrect, the error should be displayed in any case,', $model->getErrors('password'))
                ->equals(['error.password_incorrect']);

            $model->email = 'admin@ely.by';
            $model->password = 'wrong-password';
            $model->validate(['password']);
            expect($model->getErrors('password'))->equals(['error.password_incorrect']);
        });

        $this->specify('no errors if email and password is correct and exists in database', function() use ($model) {
            $model->email = 'admin@ely.by';
            $model->password = 'password_0';
            $model->validate(['password']);
            expect($model->getErrors('password'))->isEmpty();
        });
    }

    public function testLoginNoUser() {
        $model = new AuthenticationForm([
            'email'    => 'non-exist@valid.mail',
            'password' => 'not_existing_password',
        ]);

        $this->specify('user should not be able to login, when there is no identity', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginWrongPassword() {
        $model = new AuthenticationForm([
            'email'    => 'admin@ely.by',
            'password' => 'wrong_password',
        ]);

        $this->specify('user should not be able to login with wrong password', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('error message should be set', $model->errors)->hasKey('password');
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginCorrect() {
        $model = new AuthenticationForm([
            'email'    => 'admin@ely.by',
            'password' => 'password_0',
        ]);

        $this->specify('user should be able to login with correct credentials', function () use ($model) {
            expect('model should login user', $model->login())->true();
            expect('error message should not be set', $model->errors)->hasntKey('password');
            expect('user should be logged in', Yii::$app->user->isGuest)->false();
        });
    }

    public function fixtures() {
        return [
            'user' => [
                'class' => AccountFixture::className(),
                'dataFile' => '@tests/codeception/api/unit/fixtures/data/models/accounts.php'
            ],
        ];
    }

}
