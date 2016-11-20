<?php
namespace codeception\api\unit\models\authentication;

use api\components\User\RenewResult;
use api\models\authentication\RefreshTokenForm;
use Codeception\Specify;
use common\models\AccountSession;
use tests\codeception\api\unit\TestCase;
use tests\codeception\common\fixtures\AccountSessionFixture;

class RefreshTokenFormTest extends TestCase {
    use Specify;

    public function _fixtures() {
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
            expect($model->getErrors('refresh_token'))->equals(['error.refresh_token_not_exist']);
        });

        $this->specify('no errors if token exists', function() {
            /** @var RefreshTokenForm $model */
            $model = new class extends RefreshTokenForm {
                public function getSession() {
                    return new AccountSession();
                }
            };
            $model->validateRefreshToken();
            expect($model->getErrors('refresh_token'))->isEmpty();
        });
    }

    public function testRenew() {
        $model = new RefreshTokenForm();
        $model->refresh_token = $this->tester->grabFixture('sessions', 'admin')['refresh_token'];
        $this->assertInstanceOf(RenewResult::class, $model->renew());
    }

}
