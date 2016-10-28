<?php
namespace codeception\api\unit\models;

use api\models\AccountIdentity;
use Codeception\Specify;
use Emarref\Jwt\Claim;
use Emarref\Jwt\Encryption\Factory as EncryptionFactory;
use Emarref\Jwt\Jwt;
use Emarref\Jwt\Token;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;
use yii\web\IdentityInterface;

/**
 * @property AccountIdentity $accounts
 */
class AccountIdentityTest extends TestCase {
    use Specify;
    use ProtectedCaller;

    public function _fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken() {
        $identity = AccountIdentity::findIdentityByAccessToken($this->generateToken());
        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertEquals($this->tester->grabFixture('accounts', 'admin')['id'], $identity->getId());
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
        $token->addClaim(new Claim\JwtId($this->tester->grabFixture('accounts', 'admin')['id']));
        $expiredToken = (new Jwt())->serialize($token, EncryptionFactory::create(Yii::$app->user->getAlgorithm()));

        AccountIdentity::findIdentityByAccessToken($expiredToken);
    }

    /**
     * @expectedException \yii\web\UnauthorizedHttpException
     * @expectedExceptionMessage Incorrect token
     */
    public function testFindIdentityByAccessTokenWithEmptyToken() {
        AccountIdentity::findIdentityByAccessToken('');
    }

    protected function generateToken() {
        /** @var \api\components\User\Component $component */
        $component = Yii::$app->user;
        /** @var AccountIdentity $account */
        $account = AccountIdentity::findOne($this->tester->grabFixture('accounts', 'admin')['id']);

        $token = $this->callProtected($component, 'createToken', $account);

        return $this->callProtected($component, 'serializeToken', $token);
    }

}
