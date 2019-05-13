<?php
namespace codeception\api\unit\models\authentication;

use api\components\User\AuthenticationResult;
use api\models\authentication\RefreshTokenForm;
use api\tests\unit\TestCase;
use Codeception\Specify;
use common\models\AccountSession;
use common\tests\fixtures\AccountSessionFixture;

class RefreshTokenFormTest extends TestCase {
    use Specify;

    public function _fixtures(): array {
        return [
            'sessions' => AccountSessionFixture::class,
        ];
    }

    public function testValidateRefreshToken() {
        $this->specify('error.refresh_token_not_exist if passed token not exists', function() {
            /** @var RefreshTokenForm $model */
            $model = new class extends RefreshTokenForm {
                public function getSession() {
                    return null;
                }
            };
            $model->validateRefreshToken();
            $this->assertSame(['error.refresh_token_not_exist'], $model->getErrors('refresh_token'));
        });

        $this->specify('no errors if token exists', function() {
            /** @var RefreshTokenForm $model */
            $model = new class extends RefreshTokenForm {
                public function getSession() {
                    return new AccountSession();
                }
            };
            $model->validateRefreshToken();
            $this->assertEmpty($model->getErrors('refresh_token'));
        });
    }

    public function testRenew() {
        $model = new RefreshTokenForm();
        $model->refresh_token = $this->tester->grabFixture('sessions', 'admin')['refresh_token'];
        $this->assertInstanceOf(AuthenticationResult::class, $model->renew());
    }

}
