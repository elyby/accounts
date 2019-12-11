<?php
declare(strict_types=1);

namespace api\tests\unit\components\User;

use api\components\User\LegacyOAuth2Identity;
use api\tests\unit\TestCase;
use common\tests\fixtures;
use yii\web\UnauthorizedHttpException;

class LegacyOAuth2IdentityTest extends TestCase {

    public function _fixtures(): array {
        return [
            fixtures\LegacyOauthAccessTokenFixture::class,
            fixtures\LegacyOauthAccessTokenScopeFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken() {
        $identity = LegacyOAuth2Identity::findIdentityByAccessToken('ZZQP8sS9urzriy8N9h6FwFNMOH3PkZ5T5PLqS6SX');
        $this->assertSame('ZZQP8sS9urzriy8N9h6FwFNMOH3PkZ5T5PLqS6SX', $identity->getId());
    }

    public function testFindIdentityByAccessTokenWithNonExistsToken() {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Incorrect token');

        LegacyOAuth2Identity::findIdentityByAccessToken('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testFindIdentityByAccessTokenWithExpiredToken() {
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Token expired');

        LegacyOAuth2Identity::findIdentityByAccessToken('rc0sOF1SLdOxuD3bJcCQENmGTeYrGgy12qJScMx4');
    }

}
