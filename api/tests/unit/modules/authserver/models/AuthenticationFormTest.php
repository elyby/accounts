<?php
declare(strict_types=1);

namespace codeception\api\unit\modules\authserver\models;

use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\models\AuthenticationForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\OauthClientFixture;
use OTPHP\TOTP;
use function Ramsey\Uuid\v4 as uuid4;

class AuthenticationFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'oauthClients' => OauthClientFixture::class,
        ];
    }

    public function testAuthenticateByValidCredentials() {
        $authForm = new AuthenticationForm();
        $authForm->username = 'admin';
        $authForm->password = 'password_0';
        $authForm->clientToken = uuid4();
        $result = $authForm->authenticate()->getResponseData();
        $this->assertMatchesRegularExpression('/^[\w=-]+\.[\w=-]+\.[\w=-]+$/', $result['accessToken']);
        $this->assertSame($authForm->clientToken, $result['clientToken']);
        $this->assertSame('df936908b2e1544d96f82977ec213022', $result['selectedProfile']['id']);
        $this->assertSame('Admin', $result['selectedProfile']['name']);
        $this->assertTrue(OauthSession::find()->andWhere([
            'account_id' => 1,
            'client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER,
        ])->exists());
        $this->assertArrayNotHasKey('user', $result);

        $authForm->requestUser = true;
        $result = $authForm->authenticate()->getResponseData();
        $this->assertSame([
            'id' => 'df936908b2e1544d96f82977ec213022',
            'username' => 'Admin',
            'properties' => [
                [
                    'name' => 'preferredLanguage',
                    'value' => 'en',
                ],
            ],
        ], $result['user']);
    }

    public function testAuthenticateByValidCredentialsWith2FA() {
        $authForm = new AuthenticationForm();
        $authForm->username = 'otp@gmail.com';
        $authForm->password = 'password_0:' . TOTP::create('BBBB')->now();
        $authForm->clientToken = uuid4();

        // Just ensure that there is no exception
        $this->expectNotToPerformAssertions();

        $authForm->authenticate();
    }

    /**
     * This is a special case which ensures that if the user has a password that looks like
     * a two-factor code passed in the password field, than he can still log in into his account
     */
    public function testAuthenticateEdgyCaseFor2FA() {
        /** @var Account $account */
        $account = Account::findOne(['email' => 'admin@ely.by']);
        $account->setPassword('password_0:123456');
        $account->save();

        $authForm = new AuthenticationForm();
        $authForm->username = 'admin@ely.by';
        $authForm->password = 'password_0:123456';
        $authForm->clientToken = uuid4();

        // Just ensure that there is no exception
        $this->expectNotToPerformAssertions();

        $authForm->authenticate();
    }

    /**
     * @dataProvider getInvalidCredentialsCases
     */
    public function testAuthenticateByWrongCredentials(
        string $expectedExceptionMessage,
        string $login,
        string $password,
        string $totp = null
    ) {
        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $authForm = new AuthenticationForm();
        $authForm->username = $login;
        $authForm->password = $password . ($totp ? ":{$totp}" : '');
        $authForm->clientToken = uuid4();
        $authForm->authenticate();
    }

    public function getInvalidCredentialsCases() {
        yield ['Invalid credentials. Invalid nickname or password.', 'wrong-username', 'wrong-password'];
        yield ['Invalid credentials. Invalid email or password.', 'wrong-email@ely.by', 'wrong-password'];
        yield ['This account has been suspended.', 'Banned', 'password_0'];
        yield ['Account protected with two factor auth.', 'AccountWithEnabledOtp', 'password_0'];
        yield ['Invalid credentials. Invalid nickname or password.', 'AccountWithEnabledOtp', 'password_0', '123456'];
    }

}
