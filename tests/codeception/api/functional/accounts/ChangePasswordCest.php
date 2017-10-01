<?php
namespace tests\codeception\api\functional\accounts;

use common\models\Account;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\_pages\AuthenticationRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class ChangePasswordCest {

    /**
     * @var AccountsRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AccountsRoute($I);
    }

    public function _after() {
        /** @var Account $account */
        $account = Account::findOne(1);
        $account->setPassword('password_0');
        $account->save();
    }

    public function testChangePassword(FunctionalTester $I) {
        $I->wantTo('change my password');
        $id = $I->amAuthenticated();

        $this->route->changePassword($id, 'password_0', 'new-password', 'new-password');
        $this->assertSuccessResponse($I);

        $I->notLoggedIn();

        $loginRoute = new AuthenticationRoute($I);
        $loginRoute->login('Admin', 'new-password');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testChangePasswordInternal(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['change_account_password', 'escape_identity_verification']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->changePassword(1, null, 'new-password-1', 'new-password-1');
        $this->assertSuccessResponse($I);
    }

    private function assertSuccessResponse(FunctionalTester $I): void {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
