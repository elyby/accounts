<?php
declare(strict_types=1);

namespace api\tests\_support\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\authentication\RegistrationForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\EmailActivation;
use common\models\UsernameHistory;
use common\tasks\SendRegistrationEmail;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\EmailActivationFixture;
use common\tests\fixtures\UsernameHistoryFixture;
use common\tests\helpers\Mock;
use GuzzleHttp\ClientInterface;
use Yii;
use yii\validators\EmailValidator;
use yii\web\Request;
use const common\LATEST_RULES_VERSION;

class RegistrationFormTest extends TestCase {

    protected function setUp() {
        parent::setUp();
        $this->mockRequest();
        Yii::$container->set(ReCaptchaValidator::class, new class(mock(ClientInterface::class)) extends ReCaptchaValidator {
            public function validateValue($value) {
                return null;
            }
        });
    }

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
            'usernameHistory' => UsernameHistoryFixture::class,
        ];
    }

    public function testValidatePasswordAndRePasswordMatch() {
        $model = new RegistrationForm([
            'password' => 'enough-length',
            'rePassword' => 'but-mismatch',
        ]);
        $this->assertFalse($model->validate(['rePassword']));
        $this->assertSame(['error.rePassword_does_not_match'], $model->getErrors('rePassword'));

        $model = new RegistrationForm([
            'password' => 'enough-length',
            'rePassword' => 'enough-length',
        ]);
        $this->assertTrue($model->validate(['rePassword']));
        $this->assertEmpty($model->getErrors('rePassword'));
    }

    public function testSignup() {
        Mock::func(EmailValidator::class, 'checkdnsrr')->andReturn(true);
        Mock::func(EmailValidator::class, 'dns_get_record')->andReturn(['']);
        $model = new RegistrationForm([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
            'password' => 'some_password',
            'rePassword' => 'some_password',
            'rulesAgreement' => true,
            'lang' => 'ru',
        ]);

        $account = $model->signup();

        $this->expectSuccessRegistration($account);
        $this->assertSame('ru', $account->lang, 'lang is set');
    }

    public function testSignupWithDefaultLanguage() {
        Mock::func(EmailValidator::class, 'checkdnsrr')->andReturn(true);
        Mock::func(EmailValidator::class, 'dns_get_record')->andReturn(['']);
        $model = new RegistrationForm([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
            'password' => 'some_password',
            'rePassword' => 'some_password',
            'rulesAgreement' => true,
        ]);

        $account = $model->signup();

        $this->expectSuccessRegistration($account);
        $this->assertSame('en', $account->lang, 'lang is set');
    }

    /**
     * @param Account|null $account
     */
    private function expectSuccessRegistration($account) {
        $this->assertInstanceOf(Account::class, $account, 'user should be valid');
        $this->assertTrue($account->validatePassword('some_password'), 'password should be correct');
        $this->assertNotEmpty($account->uuid, 'uuid is set');
        $this->assertNotNull($account->registration_ip, 'registration_ip is set');
        $this->assertSame(LATEST_RULES_VERSION, $account->rules_agreement_version, 'actual rules version is set');
        $this->assertTrue(Account::find()->andWhere([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
        ])->exists(), 'user model exists in database');
        /** @var EmailActivation $activation */
        $activation = EmailActivation::find()
            ->andWhere([
                'account_id' => $account->id,
                'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
            ])
            ->one();
        $this->assertInstanceOf(EmailActivation::class, $activation, 'email activation code exists in database');
        $this->assertTrue(UsernameHistory::find()->andWhere([
            'username' => $account->username,
            'account_id' => $account->id,
            'applied_in' => $account->created_at,
        ])->exists(), 'username history record exists in database');

        /** @var SendRegistrationEmail $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(SendRegistrationEmail::class, $job);
        $this->assertSame($account->username, $job->username);
        $this->assertSame($account->email, $job->email);
        $this->assertSame($account->lang, $job->locale);
        $this->assertSame($activation->key, $job->code);
        $this->assertSame('http://localhost/activation/' . $activation->key, $job->link);
    }

    private function mockRequest($ip = '88.225.20.236') {
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getUserIP'])
            ->getMock();

        $request
            ->method('getUserIP')
            ->willReturn($ip);

        Yii::$app->set('request', $request);

        return $request;
    }

}
