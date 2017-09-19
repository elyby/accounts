<?php
namespace tests\codeception\api\functional;

use common\rbac\Permissions as P;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class AccountBanCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testBanAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([P::BLOCK_ACCOUNT]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->ban(1);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testBanBannedAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([P::BLOCK_ACCOUNT]);
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
