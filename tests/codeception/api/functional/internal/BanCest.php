<?php
namespace tests\codeception\api\functional\internal;

use common\models\OauthScope as S;
use tests\codeception\api\_pages\InternalRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class BanCest {

    /**
     * @var InternalRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new InternalRoute($I);
    }

    public function testBanAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([S::ACCOUNT_BLOCK]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->ban(1);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testBanBannedAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([S::ACCOUNT_BLOCK]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->ban(10);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.account_already_banned',
            ],
        ]);
    }

}
