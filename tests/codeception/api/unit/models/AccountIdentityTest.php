<?php
namespace codeception\api\unit\models;

use api\models\AccountIdentity;
use Codeception\Specify;
use tests\codeception\api\unit\DbTestCase;
use tests\codeception\common\_support\ProtectedCaller;
use tests\codeception\common\fixtures\AccountFixture;
use Yii;
use yii\web\IdentityInterface;

/**
 * @property AccountIdentity $accounts
 */
class AccountIdentityTest extends DbTestCase {
    use Specify;
    use ProtectedCaller;

    public function fixtures() {
        return [
            'accounts' => AccountFixture::class,
        ];
    }

    public function testFindIdentityByAccessToken() {
        $identity = AccountIdentity::findIdentityByAccessToken($this->generateToken());
        $this->assertInstanceOf(IdentityInterface::class, $identity);
        $this->assertEquals($this->accounts['admin']['id'], $identity->getId());
    }

    /**
     * @expectedException \yii\web\UnauthorizedHttpException
     * @expectedExceptionMessage Token expired
     */
    public function testFindIdentityByAccessTokenWithExpiredToken() {
        $expiredToken = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJodHRwOlwvXC9sb2NhbGhvc3Q6ODA4MCIsImlzcyI6Imh0d' .
                        'HA6XC9cL2xvY2FsaG9zdDo4MDgwIiwiaWF0IjoxNDY0NTkzMTkzLCJleHAiOjE0NjQ1OTY3OTN9.DV' .
                        '8uwh0OQhBYXkrNvxwJeO-kEjb9MQeLr3-6GoHM7RY';

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
        $account = AccountIdentity::findOne($this->accounts['admin']['id']);

        $token = $this->callProtected($component, 'createToken', $account);

        return $this->callProtected($component, 'serializeToken', $token);
    }

}
