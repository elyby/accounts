<?php
namespace codeception\console\unit\controllers;

use common\models\OauthAccessToken;
use console\controllers\CleanupController;
use tests\codeception\common\fixtures\OauthAccessTokenFixture;
use tests\codeception\console\unit\TestCase;
use Yii;

class CleanupControllerTest extends TestCase {

    public function _fixtures() {
        return [
            'accessTokens' => OauthAccessTokenFixture::class,
        ];
    }

    public function testActionAccessTokens() {
        /** @var OauthAccessToken $validAccessToken */
        $validAccessToken = $this->tester->grabFixture('accessTokens', 'admin-ely');
        /** @var OauthAccessToken $expiredAccessToken */
        $expiredAccessToken = $this->tester->grabFixture('accessTokens', 'admin-ely-expired');

        $controller = new CleanupController('cleanup', Yii::$app);
        $this->assertEquals(0, $controller->actionAccessTokens());

        $this->tester->canSeeRecord(OauthAccessToken::class, ['access_token' => $validAccessToken->access_token]);
        $this->tester->cantSeeRecord(OauthAccessToken::class, ['access_token' => $expiredAccessToken->access_token]);
    }

}
