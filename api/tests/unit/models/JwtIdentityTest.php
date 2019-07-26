<?php
declare(strict_types=1);

namespace codeception\api\unit\models;

use api\components\User\JwtIdentity;
use api\tests\unit\TestCase;
use common\tests\fixtures\AccountFixture;
use Emarref\Jwt\Claim\Expiration as ExpirationClaim;
use Yii;

class JwtIdentityTest extends TestCase {

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken() {
        $token = $this->generateToken();
        $identity = JwtIdentity::findIdentityByAccessToken($token);
        $this->assertSame($token, $identity->getId());
        $this->assertSame($this->tester->grabFixture('accounts', 'admin')['id'], $identity->getAccount()->id);
    }

    /**
     * @expectedException \yii\web\UnauthorizedHttpException
     * @expectedExceptionMessage Token expired
     */
    public function testFindIdentityByAccessTokenWithExpiredToken() {
        $expiredToken = $this->generateToken(time() - 3600);
        JwtIdentity::findIdentityByAccessToken($expiredToken);
    }

    /**
     * @expectedException \yii\web\UnauthorizedHttpException
     * @expectedExceptionMessage Incorrect token
     */
    public function testFindIdentityByAccessTokenWithEmptyToken() {
        JwtIdentity::findIdentityByAccessToken('');
    }

    private function generateToken(int $expiresAt = null): string {
        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;
        /** @var \common\models\Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $token = $component->createJwtAuthenticationToken($account);
        if ($expiresAt !== null) {
            $token->addClaim(new ExpirationClaim($expiresAt));
        }

        return $component->serializeToken($token);
    }

}
