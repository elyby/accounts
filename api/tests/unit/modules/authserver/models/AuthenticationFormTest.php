<?php
declare(strict_types=1);

namespace codeception\api\unit\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\models\AuthenticateData;
use api\modules\authserver\models\AuthenticationForm;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\MinecraftAccessKey;
use common\tests\_support\ProtectedCaller;
use common\tests\fixtures\AccountFixture;
use common\tests\fixtures\MinecraftAccessKeyFixture;
use Ramsey\Uuid\Uuid;

class AuthenticationFormTest extends TestCase {
    use ProtectedCaller;

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
            'minecraftAccessKeys' => MinecraftAccessKeyFixture::class,
        ];
    }

    public function testAuthenticateByWrongNicknamePass() {
        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage('Invalid credentials. Invalid nickname or password.');

        $authForm = $this->createAuthForm();

        $authForm->username = 'wrong-username';
        $authForm->password = 'wrong-password';
        $authForm->clientToken = Uuid::uuid4();

        $authForm->authenticate();
    }

    public function testAuthenticateByWrongEmailPass() {
        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage('Invalid credentials. Invalid email or password.');

        $authForm = $this->createAuthForm();

        $authForm->username = 'wrong-email@ely.by';
        $authForm->password = 'wrong-password';
        $authForm->clientToken = Uuid::uuid4();

        $authForm->authenticate();
    }

    public function testAuthenticateByValidCredentialsIntoBlockedAccount() {
        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage('This account has been suspended.');

        $authForm = $this->createAuthForm(Account::STATUS_BANNED);

        $authForm->username = 'dummy';
        $authForm->password = 'password_0';
        $authForm->clientToken = Uuid::uuid4();

        $authForm->authenticate();
    }

    public function testAuthenticateByValidCredentials() {
        $authForm = $this->createAuthForm();

        $minecraftAccessKey = new MinecraftAccessKey();
        $minecraftAccessKey->access_token = Uuid::uuid4();
        $authForm->expects($this->once())
            ->method('createMinecraftAccessToken')
            ->willReturn($minecraftAccessKey);

        $authForm->username = 'dummy';
        $authForm->password = 'password_0';
        $authForm->clientToken = Uuid::uuid4();

        $result = $authForm->authenticate();
        $this->assertInstanceOf(AuthenticateData::class, $result);
        $this->assertSame($minecraftAccessKey->access_token, $result->getToken()->access_token);
    }

    public function testCreateMinecraftAccessToken() {
        $authForm = new AuthenticationForm();
        $authForm->clientToken = Uuid::uuid4();
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        /** @var MinecraftAccessKey $result */
        $result = $this->callProtected($authForm, 'createMinecraftAccessToken', $account);
        $this->assertInstanceOf(MinecraftAccessKey::class, $result);
        $this->assertSame($account->id, $result->account_id);
        $this->assertSame($authForm->clientToken, $result->client_token);
        $this->assertInstanceOf(MinecraftAccessKey::class, MinecraftAccessKey::findOne($result->access_token));
    }

    public function testCreateMinecraftAccessTokenWithExistsClientId() {
        $authForm = new AuthenticationForm();
        $minecraftFixture = $this->tester->grabFixture('minecraftAccessKeys', 'admin-token');
        $authForm->clientToken = $minecraftFixture['client_token'];
        /** @var Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        /** @var MinecraftAccessKey $result */
        $result = $this->callProtected($authForm, 'createMinecraftAccessToken', $account);
        $this->assertInstanceOf(MinecraftAccessKey::class, $result);
        $this->assertSame($account->id, $result->account_id);
        $this->assertSame($authForm->clientToken, $result->client_token);
        $this->assertNull(MinecraftAccessKey::findOne($minecraftFixture['access_token']));
        $this->assertInstanceOf(MinecraftAccessKey::class, MinecraftAccessKey::findOne($result->access_token));
    }

    private function createAuthForm($status = Account::STATUS_ACTIVE) {
        /** @var LoginForm|\PHPUnit\Framework\MockObject\MockObject $loginForm */
        $loginForm = $this->getMockBuilder(LoginForm::class)
            ->setMethods(['getAccount'])
            ->getMock();

        $account = new Account();
        $account->username = 'dummy';
        $account->email = 'dummy@ely.by';
        $account->status = $status;
        $account->setPassword('password_0');

        $loginForm
            ->method('getAccount')
            ->willReturn($account);

        /** @var AuthenticationForm|\PHPUnit\Framework\MockObject\MockObject $authForm */
        $authForm = $this->getMockBuilder(AuthenticationForm::class)
            ->setMethods(['createLoginForm', 'createMinecraftAccessToken'])
            ->getMock();

        $authForm
            ->method('createLoginForm')
            ->willReturn($loginForm);

        return $authForm;
    }

}
