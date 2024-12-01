<?php
namespace api\tests\functional\accounts;

use api\rbac\Permissions as P;
use api\tests\_pages\AccountsRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

class PardonCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testPardonAccount(OauthSteps $I): void {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([P::BLOCK_ACCOUNT]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->pardon(10);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testPardonNotBannedAccount(OauthSteps $I): void {
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
