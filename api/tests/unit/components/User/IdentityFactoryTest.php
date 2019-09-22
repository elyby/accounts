<?php
declare(strict_types=1);

namespace api\tests\unit\components\User;

use api\components\OAuth2\Component;
use api\components\OAuth2\Entities\AccessTokenEntity;
use api\components\User\IdentityFactory;
use api\components\User\JwtIdentity;
use api\components\User\LegacyOAuth2Identity;
use api\tests\unit\TestCase;
use Carbon\Carbon;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use Yii;
use yii\web\UnauthorizedHttpException;

class IdentityFactoryTest extends TestCase {

    public function testFindIdentityByAccessToken() {
        // Find identity by jwt token
        $identity = IdentityFactory::findIdentityByAccessToken('eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJlbHktc2NvcGVzIjoiYWNjb3VudHNfd2ViX3VzZXIiLCJpYXQiOjE1NjQ2MTA1NDIsImV4cCI6MTU2NDYxNDE0Miwic3ViIjoiZWx5fDEifQ.4Oidvuo4spvUf9hkpHR72eeqZUh2Zbxh_L8Od3vcgTj--0iOrcOEp6zwmEW6vF7BTHtjz2b3mXce61bqsCjXjQ');
        $this->assertInstanceOf(JwtIdentity::class, $identity);

        // Find identity by oauth2 token
        $accessToken = new AccessTokenEntity(mock(AbstractServer::class));
        $accessToken->setExpireTime(time() + 3600);
        $accessToken->setId('mock-token');

        /** @var AccessTokenInterface|\Mockery\MockInterface $accessTokensStorage */
        $accessTokensStorage = mock(AccessTokenInterface::class);
        $accessTokensStorage->shouldReceive('get')->with('mock-token')->andReturn($accessToken);

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class);
        $component->shouldReceive('getAccessTokenStorage')->andReturn($accessTokensStorage);
        Yii::$app->set('oauth', $component);

        $identity = IdentityFactory::findIdentityByAccessToken('mock-token');
        $this->assertInstanceOf(LegacyOAuth2Identity::class, $identity);
    }

    public function testFindIdentityByAccessTokenWithEmptyValue() {
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
