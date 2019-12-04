<?php
declare(strict_types=1);

namespace codeception\api\unit\modules\authserver\models;

use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\models\AuthenticationForm;
use api\tests\unit\TestCase;
use common\tests\fixtures\AccountFixture;
use Ramsey\Uuid\Uuid;

class AuthenticationFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testAuthenticateByValidCredentials() {
        $authForm = new AuthenticationForm();
        $authForm->username = 'admin';
        $authForm->password = 'password_0';
        $authForm->clientToken = Uuid::uuid4()->toString();
        $result = $authForm->authenticate()->getResponseData();
        $this->assertRegExp('/^[\w=-]+\.[\w=-]+\.[\w=-]+$/', $result['accessToken']);
        $this->assertSame($authForm->clientToken, $result['clientToken']);
        $this->assertSame('df936908-b2e1-544d-96f8-2977ec213022', $result['selectedProfile']['id']);
        $this->assertSame('Admin', $result['selectedProfile']['name']);
        $this->assertFalse($result['selectedProfile']['legacy']);
    }

    /**
     * @dataProvider getInvalidCredentialsCases
     */
    public function testAuthenticateByWrongNicknamePass(string $expectedFieldError, string $login, string $password) {
        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage("Invalid credentials. Invalid {$expectedFieldError} or password.");

        $authForm = new AuthenticationForm();
        $authForm->username = $login;
        $authForm->password = $password;
        $authForm->clientToken = Uuid::uuid4()->toString();
        $authForm->authenticate();
    }

    public function getInvalidCredentialsCases() {
        yield ['nickname', 'wrong-username', 'wrong-password'];
        yield ['email', 'wrong-email@ely.by', 'wrong-password'];
    }

    public function testAuthenticateByValidCredentialsIntoBlockedAccount() {
        $this->expectException(ForbiddenOperationException::class);
        $this->expectExceptionMessage('This account has been suspended.');

        $authForm = new AuthenticationForm();
        $authForm->username = 'Banned';
        $authForm->password = 'password_0';
        $authForm->clientToken = Uuid::uuid4()->toString();
        $authForm->authenticate();
    }

}
