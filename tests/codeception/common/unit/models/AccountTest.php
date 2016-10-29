<?php
namespace tests\codeception\common\unit\models;

use Codeception\Specify;
use common\components\UserPass;
use common\models\Account;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\MojangUsernameFixture;
use tests\codeception\common\unit\TestCase;
use Yii;
use const common\LATEST_RULES_VERSION;

class AccountTest extends TestCase {
    use Specify;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'mojangAccounts' => MojangUsernameFixture::class,
        ];
    }

    public function testValidateUsername() {
        $this->specify('username required', function() {
            $model = new Account(['username' => null]);
            expect($model->validate(['username']))->false();
            expect($model->getErrors('username'))->equals(['error.username_required']);
        });

        $this->specify('username should be at least 3 symbols length', function() {
            $model = new Account(['username' => 'at']);
            expect($model->validate(['username']))->false();
            expect($model->getErrors('username'))->equals(['error.username_too_short']);
        });

        $this->specify('username should be not more than 21 symbols length', function() {
            $model = new Account(['username' => 'erickskrauch_erickskrauch']);
            expect($model->validate(['username']))->false();
            expect($model->getErrors('username'))->equals(['error.username_too_long']);
        });

        $this->specify('username can contain many cool symbols', function() {
            $shouldBeValid = [
                'русский_ник', 'русский_ник_на_грани!', 'numbers1132', '*__*-Stars-*__*', '1-_.!?#$%^&*()[]',
                '[ESP]Эрик', 'Свят_помидор;', 'зроблена_ў_беларусі:)',
            ];
            foreach($shouldBeValid as $nickname) {
                $model = new Account(['username' => $nickname]);
                expect($nickname . ' passed validation', $model->validate(['username']))->true();
                expect($model->getErrors('username'))->isEmpty();
            }
        });

        $this->specify('username cannot contain some symbols', function() {
            $shouldBeInvalid = [
                'nick@name', 'spaced nick',
            ];
            foreach($shouldBeInvalid as $nickname) {
                $model = new Account(['username' => $nickname]);
                expect($nickname . ' fail validation', $model->validate('username'))->false();
                expect($model->getErrors('username'))->equals(['error.username_invalid']);
            }
        });

        $this->specify('username should be unique', function() {
            $model = new Account();
            $model->username = $this->tester->grabFixture('accounts', 'admin')['username'];
            expect($model->validate('username'))->false();
            expect($model->getErrors('username'))->equals(['error.username_not_available']);
        });
    }

    public function testValidateEmail() {
        // TODO: пропускать этот тест, если падает ошибка с недостпуностью интернет соединения
        $this->specify('email required', function() {
            $model = new Account(['email' => null]);
            expect($model->validate(['email']))->false();
            expect($model->getErrors('email'))->equals(['error.email_required']);
        });

        $this->specify('email should be not more 255 symbols (I hope it\'s impossible to register)', function() {
            $model = new Account([
                'email' => 'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
                           'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
                           'emailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemailemail' .
                           'emailemail', // = 256 symbols
            ]);
            expect($model->validate(['email']))->false();
            expect($model->getErrors('email'))->equals(['error.email_too_long']);
        });

        $this->specify('email should be email (it test can fail, if you don\'t have internet connection)', function() {
            $model = new Account(['email' => 'invalid_email']);
            expect($model->validate(['email']))->false();
            expect($model->getErrors('email'))->equals(['error.email_invalid']);
        });

        $this->specify('email should be not tempmail', function() {
            $model = new Account(['email' => 'ibrpycwyjdnt@dropmail.me']);
            expect($model->validate(['email']))->false();
            expect($model->getErrors('email'))->equals(['error.email_is_tempmail']);
        });

        $this->specify('email should be unique', function() {
            $model = new Account(['email' => $this->tester->grabFixture('accounts', 'admin')['email']]);
            expect($model->validate('email'))->false();
            expect($model->getErrors('email'))->equals(['error.email_not_available']);
        });
    }

    public function testSetPassword() {
        $this->specify('calling method should change password and set latest password hash algorithm', function() {
            $model = new Account();
            $model->setPassword('12345678');
            expect('hash should be set', $model->password_hash)->notEmpty();
            expect('validation should be passed', $model->validatePassword('12345678'))->true();
            expect('latest password hash should be used', $model->password_hash_strategy)->equals(Account::PASS_HASH_STRATEGY_YII2);
        });
    }

    public function testValidatePassword() {
        $this->specify('old Ely password should work', function() {
            $model = new Account([
                'email' => 'erick@skrauch.net',
                'password_hash' => UserPass::make('erick@skrauch.net', '12345678'),
            ]);
            expect('valid password should pass', $model->validatePassword('12345678', Account::PASS_HASH_STRATEGY_OLD_ELY))->true();
            expect('invalid password should fail', $model->validatePassword('87654321', Account::PASS_HASH_STRATEGY_OLD_ELY))->false();
        });

        $this->specify('modern hash algorithm should work', function() {
            $model = new Account([
                'password_hash' => Yii::$app->security->generatePasswordHash('12345678'),
            ]);
            expect('valid password should pass', $model->validatePassword('12345678', Account::PASS_HASH_STRATEGY_YII2))->true();
            expect('invalid password should fail', $model->validatePassword('87654321', Account::PASS_HASH_STRATEGY_YII2))->false();
        });

        $this->specify('if second argument is not pass model value should be used', function() {
            $model = new Account([
                'email' => 'erick@skrauch.net',
                'password_hash_strategy' => Account::PASS_HASH_STRATEGY_OLD_ELY,
                'password_hash' => UserPass::make('erick@skrauch.net', '12345678'),
            ]);
            expect('valid password should pass', $model->validatePassword('12345678'))->true();
            expect('invalid password should fail', $model->validatePassword('87654321'))->false();

            $model = new Account([
                'password_hash_strategy' => Account::PASS_HASH_STRATEGY_YII2,
                'password_hash' => Yii::$app->security->generatePasswordHash('12345678'),
            ]);
            expect('valid password should pass', $model->validatePassword('12345678'))->true();
            expect('invalid password should fail', $model->validatePassword('87654321'))->false();
        });
    }

    public function testHasMojangUsernameCollision() {
        $this->specify('Expect true if collision with current username', function() {
            $model = new Account();
            $model->username = 'ErickSkrauch';
            expect($model->hasMojangUsernameCollision())->true();
        });

        $this->specify('Expect false if some rare username without any collision on Mojang', function() {
            $model = new Account();
            $model->username = 'rare-username';
            expect($model->hasMojangUsernameCollision())->false();
        });
    }

    public function testGetProfileLink() {
        $model = new Account();
        $model->id = '123';
        $this->assertEquals('http://ely.by/u123', $model->getProfileLink());
    }

    public function testIsAgreedWithActualRules() {
        $this->specify('get false, if rules field set in null', function() {
            $model = new Account();
            expect($model->isAgreedWithActualRules())->false();
        });

        $this->specify('get false, if rules field have version less, then actual', function() {
            $model = new Account();
            $model->rules_agreement_version = 0;
            expect($model->isAgreedWithActualRules())->false();
        });

        $this->specify('get true, if rules field have equals rules version', function() {
            $model = new Account();
            $model->rules_agreement_version = LATEST_RULES_VERSION;
            expect($model->isAgreedWithActualRules())->true();
        });
    }

    public function testSetRegistrationIp() {
        $account = new Account();
        $account->setRegistrationIp('42.72.205.204');
        $this->assertEquals('42.72.205.204', inet_ntop($account->registration_ip));
        $account->setRegistrationIp('2001:1620:28:1:b6f:8bca:93:a116');
        $this->assertEquals('2001:1620:28:1:b6f:8bca:93:a116', inet_ntop($account->registration_ip));
        $account->setRegistrationIp(null);
        $this->assertNull($account->registration_ip);
    }

    public function testGetRegistrationIp() {
        $account = new Account();
        $account->setRegistrationIp('42.72.205.204');
        $this->assertEquals('42.72.205.204', $account->getRegistrationIp());
        $account->setRegistrationIp('2001:1620:28:1:b6f:8bca:93:a116');
        $this->assertEquals('2001:1620:28:1:b6f:8bca:93:a116', $account->getRegistrationIp());
        $account->setRegistrationIp(null);
        $this->assertNull($account->getRegistrationIp());
    }

}
