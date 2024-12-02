<?php
namespace api\tests\functional\accounts;

use api\tests\_pages\AccountsRoute;
use api\tests\_pages\AuthenticationRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use common\models\Account;

class ChangePasswordCest {

    private AccountsRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new AccountsRoute($I);
    }

    public function _after(): void {
        /** @var Account $account */
        $account = Account::findOne(1);
        $account->setPassword('password_0');
        $account->save();
    }

    public function testChangePassword(FunctionalTester $I): void {
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

    public function testChangePasswordInternal(OauthSteps $I): void {
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
