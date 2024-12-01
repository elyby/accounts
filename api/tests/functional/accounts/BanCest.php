<?php
namespace api\tests\functional\accounts;

use api\rbac\Permissions as P;
use api\tests\_pages\AccountsRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

class BanCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function testBanAccount(OauthSteps $I): void {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant([P::BLOCK_ACCOUNT]);
        $I->amBearerAuthenticated($accessToken);

        $this->route->ban(1);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testBanBannedAccount(OauthSteps $I): void {
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
