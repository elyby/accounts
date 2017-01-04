<?php
namespace tests\codeception\api\functional\internal;

use common\models\OauthScope as S;
use tests\codeception\api\_pages\InternalRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class PardonCest {

    /**
     * @var InternalRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new InternalRoute($I);
    }

    public function testPardonAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([S::ACCOUNT_BLOCK]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->pardon(10);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testPardonNotBannedAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([S::ACCOUNT_BLOCK]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->pardon(1);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'account' => 'error.account_not_banned',
            ],
        ]);
    }

}
