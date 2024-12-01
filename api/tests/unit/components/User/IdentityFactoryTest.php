<?php
declare(strict_types=1);

namespace api\tests\unit\components\User;

use api\components\User\IdentityFactory;
use api\components\User\JwtIdentity;
use api\components\User\LegacyOAuth2Identity;
use api\tests\unit\TestCase;
use Carbon\Carbon;
use common\tests\fixtures;
use yii\web\UnauthorizedHttpException;

class IdentityFactoryTest extends TestCase {

    public function _fixtures(): array {
        return [
            fixtures\LegacyOauthAccessTokenFixture::class,
            fixtures\LegacyOauthAccessTokenScopeFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken(): void {
        // Find identity by the JWT
        $identity = IdentityFactory::findIdentityByAccessToken('eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ2MTA1NDIsImV4cCI6MTU2NDYxNDE0Miwic3ViIjoiZWx5fDEifQ.4Oidvuo4spvUf9hkpHR72eeqZUh2Zbxh_L8Od3vcgTj--0iOrcOEp6zwmEW6vF7BTHtjz2b3mXce61bqsCjXjQ');
        $this->assertInstanceOf(JwtIdentity::class, $identity);

        // Find identity by the legacy OAuth2 token
        $identity = IdentityFactory::findIdentityByAccessToken('ZZQP8sS9urzriy8N9h6FwFNMOH3PkZ5T5PLqS6SX');
        $this->assertInstanceOf(LegacyOAuth2Identity::class, $identity);
    }

    public function testFindIdentityByAccessTokenWithEmptyValue(): void {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Incorrect token');
        IdentityFactory::findIdentityByAccessToken('');
    }

    protected function _setUp() {
        parent::_setUp();
        Carbon::setTestNow(Carbon::create(2019, 8, 1, 1, 2, 22, 'Europe/Minsk'));
    }

    protected function _tearDown() {
        parent::_tearDown();
        Carbon::setTestNow();
    }

}
