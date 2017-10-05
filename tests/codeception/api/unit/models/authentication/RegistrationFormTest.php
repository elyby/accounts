<?php
namespace tests\codeception\api\models\authentication;

use api\components\ReCaptcha\Validator as ReCaptchaValidator;
use api\models\authentication\RegistrationForm;
use Codeception\Specify;
use common\models\Account;
use common\models\EmailActivation;
use common\models\UsernameHistory;
use GuzzleHttp\ClientInterface;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountFixture;
use tests\codeception\common\fixtures\EmailActivationFixture;
use tests\codeception\common\fixtures\UsernameHistoryFixture;
use tests\codeception\common\helpers\Mock;
use Yii;
use yii\validators\EmailValidator;
use yii\web\Request;
use const common\LATEST_RULES_VERSION;

class RegistrationFormTest extends TestCase {
    use Specify;

    public function setUp() {
        parent::setUp();
        $this->mockRequest();
        Yii::$container->set(ReCaptchaValidator::class, new class(mock(ClientInterface::class)) extends ReCaptchaValidator {
            public function validateValue($value) {
                return null;
            }
        });
    }

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
            'emailActivations' => EmailActivationFixture::class,
            'usernameHistory' => UsernameHistoryFixture::class,
        ];
    }

    public function testValidatePasswordAndRePasswordMatch() {
        $this->specify('error.rePassword_does_not_match if password and rePassword not match', function() {
            $model = new RegistrationForm([
                'password' => 'enough-length',
                'rePassword' => 'password',
            ]);
            expect($model->validate(['rePassword']))->false();
            expect($model->getErrors('rePassword'))->equals(['error.rePassword_does_not_match']);
        });

        $this->specify('no errors if password and rePassword match', function() {
            $model = new RegistrationForm([
                'password' => 'enough-length',
                'rePassword' => 'enough-length',
            ]);
            expect($model->validate(['rePassword']))->true();
            expect($model->getErrors('rePassword'))->isEmpty();
        });
    }

    public function testSignup() {
        Mock::func(EmailValidator::class, 'checkdnsrr')->andReturnTrue();
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
        $this->assertEquals('ru', $account->lang, 'lang is set');
    }

    public function testSignupWithDefaultLanguage() {
        Mock::func(EmailValidator::class, 'checkdnsrr')->andReturnTrue();
        $model = new RegistrationForm([
            'username' => 'some_username',
            'email' => 'some_email@example.com',
            'password' => 'some_password',
            'rePassword' => 'some_password',
            'rulesAgreement' => true,
        ]);

        $account = $model->signup();

        $this->expectSuccessRegistration($account);
        $this->assertEquals('en', $account->lang, 'lang is set');
    }

    /**
     * @param Account|null $account
     */
    private function expectSuccessRegistration($account) {
        $this->assertInstanceOf(Account::class, $account, 'user should be valid');
        $this->assertTrue($account->validatePassword('some_password'), 'password should be correct');
        $this->assertNotEmpty($account->uuid, 'uuid is set');
        $this->assertNotNull($account->registration_ip, 'registration_ip is set');
        $this->assertEquals(LATEST_RULES_VERSION, $account->rules_agreement_version, 'actual rules version is set');
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
        $this->tester->canSeeEmailIsSent(1);
        /** @var \yii\swiftmailer\Message $email */
        $email = $this->tester->grabSentEmails()[0];
        $body = $email->getSwiftMessage()->getBody();
        $this->assertContains($activation->key, $body);
        $this->assertContains('/activation/' . $activation->key, $body);
    }

    private function mockRequest($ip = '88.225.20.236') {
        $request = $this->getMockBuilder(Request::class)
            ->setMethods(['getUserIP'])
            ->getMock();

        $request
            ->expects($this->any())
            ->method('getUserIP')
            ->will($this->returnValue($ip));

        Yii::$app->set('request', $request);

        return $request;
    }

}
