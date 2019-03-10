<?php
namespace api\tests\unit\components\User;

use api\components\User\AuthenticationResult;
use api\tests\unit\TestCase;
use common\models\Account;
use common\models\AccountSession;
use Emarref\Jwt\Algorithm\Hs256;
use Emarref\Jwt\Claim\Expiration;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;

class JwtAuthenticationResultTest extends TestCase {

    public function testGetAccount() {
        $account = new Account();
        $account->id = 123;
        $model = new AuthenticationResult($account, '', null);
        $this->assertSame($account, $model->getAccount());
    }

    public function testGetJwt() {
        $model = new AuthenticationResult(new Account(), 'mocked jwt', null);
        $this->assertSame('mocked jwt', $model->getJwt());
    }

    public function testGetSession() {
        $model = new AuthenticationResult(new Account(), '', null);
        $this->assertNull($model->getSession());

        $session = new AccountSession();
        $session->id = 321;
        $model = new AuthenticationResult(new Account(), '', $session);
        $this->assertSame($session, $model->getSession());
    }

    public function testGetAsResponse() {
        $jwtToken = $this->createJwtToken(time() + 3600);
        $model = new AuthenticationResult(new Account(), $jwtToken, null);
        $result = $model->getAsResponse();
        $this->assertSame($jwtToken, $result['access_token']);
        $this->assertSame(3600, $result['expires_in']);

        /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
        $jwtToken = $this->createJwtToken(time() + 86400);
        $session = new AccountSession();
        $session->refresh_token = 'refresh token';
        $model = new AuthenticationResult(new Account(), $jwtToken, $session);
        $result = $model->getAsResponse();
        $this->assertSame($jwtToken, $result['access_token']);
        $this->assertSame('refresh token', $result['refresh_token']);
        $this->assertSame(86400, $result['expires_in']);
    }

    private function createJwtToken(int $expires): string {
        $token = new Token();
        $token->addClaim(new Expiration($expires));

        return (new Jwt())->serialize($token, EncryptionFactory::create(new Hs256('123')));
    }

}
