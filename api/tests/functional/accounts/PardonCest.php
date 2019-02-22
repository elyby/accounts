<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use common\rbac\Permissions as P;

class PardonCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function testPardonAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([P::BLOCK_ACCOUNT]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->pardon(10);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testPardonNotBannedAccount(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([P::BLOCK_ACCOUNT]);
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
