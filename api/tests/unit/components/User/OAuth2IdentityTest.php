<?php
declare(strict_types=1);

namespace api\tests\unit\components\User;

use api\components\OAuth2\Component;
use api\components\OAuth2\Entities\AccessTokenEntity;
use api\components\User\OAuth2Identity;
use api\tests\unit\TestCase;
use League\OAuth2\Server\AbstractServer;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use Yii;
use yii\web\UnauthorizedHttpException;

class OAuth2IdentityTest extends TestCase {

    public function testFindIdentityByAccessToken() {
        $accessToken = new AccessTokenEntity(mock(AbstractServer::class));
        $accessToken->setExpireTime(time() + 3600);
        $accessToken->setId('mock-token');
        $this->mockFoundedAccessToken($accessToken);

        $identity = OAuth2Identity::findIdentityByAccessToken('mock-token');
        $this->assertSame('mock-token', $identity->getId());
    }

    public function testFindIdentityByAccessTokenWithNonExistsToken() {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Incorrect token');

        OAuth2Identity::findIdentityByAccessToken('not exists token');
    }

    public function testFindIdentityByAccessTokenWithExpiredToken() {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Token expired');

        $accessToken = new AccessTokenEntity(mock(AbstractServer::class));
        $accessToken->setExpireTime(time() - 3600);
        $this->mockFoundedAccessToken($accessToken);

        OAuth2Identity::findIdentityByAccessToken('mock-token');
    }

    private function mockFoundedAccessToken(AccessTokenEntity $accessToken) {
        /** @var AccessTokenInterface|\Mockery\MockInterface $accessTokensStorage */
        $accessTokensStorage = mock(AccessTokenInterface::class);
        $accessTokensStorage->shouldReceive('get')->with('mock-token')->andReturn($accessToken);

        /** @var Component|\Mockery\MockInterface $component */
        $component = mock(Component::class);
        $component->shouldReceive('getAccessTokenStorage')->andReturn($accessTokensStorage);
        Yii::$app->set('oauth', $component);
    }

}
