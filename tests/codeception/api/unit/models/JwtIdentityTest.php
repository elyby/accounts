<?php
namespace codeception\api\unit\models;

use api\components\User\IdentityInterface;
use api\components\User\Jwt;
use api\components\User\JwtIdentity;
use Codeception\Specify;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Token;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;

class JwtIdentityTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function _fixtures(): array {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken() {
        $token = $this->generateToken();
        $identity = JwtIdentity::findIdentityByAccessToken($token);
        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertEquals($token, $identity->getId());
        $this->assertEquals($this->tester->grabFixture('accounts', 'admin')['id'], $identity->getAccount()->id);
    }

    /**
     * @expectedException \yii\web\UnauthorizedHttpException
     * @expectedExceptionMessage Token expired
     */
    public function testFindIdentityByAccessTokenWithExpiredToken() {
        $token = new Token();
        $token->addClaim(new Claim\Audience('http://localhost'));
        $token->addClaim(new Claim\Issuer('http://localhost'));
        $token->addClaim(new Claim\IssuedAt(1464593193));
        $token->addClaim(new Claim\Expiration(1464596793));
        $token->addClaim(new Claim\Subject('ely|' . $this->tester->grabFixture('accounts', 'admin')['id']));
        $expiredToken = (new Jwt())->serialize($token, EncryptionFactory::create(Yii::$app->user->getAlgorithm()));

        JwtIdentity::findIdentityByAccessToken($expiredToken);
    }

    /**
     * @expectedException \yii\web\UnauthorizedHttpException
     * @expectedExceptionMessage Incorrect token
     */
    public function testFindIdentityByAccessTokenWithEmptyToken() {
        JwtIdentity::findIdentityByAccessToken('');
    }

    protected function generateToken() {
        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;
        /** @var \common\models\Account $account */
        $account = $this->tester->grabFixture('accounts', 'admin');
        $token = $this->callProtected($component, 'createToken', $account);

        return $this->callProtected($component, 'serializeToken', $token);
    }

}
