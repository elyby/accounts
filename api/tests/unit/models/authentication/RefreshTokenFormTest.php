<?php
declare(strict_types=1);

namespace codeception\api\unit\models\authentication;

use api\models\authentication\RefreshTokenForm;
use api\tests\unit\TestCase;
use common\models\AccountSession;
use common\tests\fixtures\AccountSessionFixture;
use Yii;
use yii\web\Request;

class RefreshTokenFormTest extends TestCase {

    public function _fixtures(): array {
        return [
            'sessions' => AccountSessionFixture::class,
        ];
    }

    public function testRenew() {
        $request = $this->createPartialMock(Request::class, ['getUserIP']);
        $request->method('getUserIP')->willReturn('10.1.2.3');
        Yii::$app->set('request', $request);

        $model = new RefreshTokenForm();
        $model->refresh_token = 'SOutIr6Seeaii3uqMVy3Wan8sKFVFrNz';
        $result = $model->renew();
        $this->assertNotNull($result);
        $this->assertSame('SOutIr6Seeaii3uqMVy3Wan8sKFVFrNz', $result->getRefreshToken());

        $token = $result->getToken();
        $this->assertSame('ely|1', $token->getClaim('sub'));
        $this->assertSame('accounts_web_user', $token->getClaim('scope'));
        $this->assertEqualsWithDelta(time(), $token->getClaim('iat'), 5);
        $this->assertEqualsWithDelta(time() + 3600, $token->getClaim('exp'), 5);
        $this->assertSame(1, $token->getClaim('jti'));

        /** @var AccountSession $session */
        $session = AccountSession::findOne(['refresh_token' => 'SOutIr6Seeaii3uqMVy3Wan8sKFVFrNz']);
        $this->assertEqualsWithDelta(time(), $session->last_refreshed_at, 5);
        $this->assertSame('10.1.2.3', $session->getReadableIp());
    }

    public function testRenewWithInvalidRefreshToken() {
        $model = new RefreshTokenForm();
        $model->refresh_token = 'unknown refresh token';
        $this->assertNull($model->renew());
        $this->assertSame(['error.refresh_token_not_exist'], $model->getErrors('refresh_token'));
    }

}
