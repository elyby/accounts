<?php
namespace tests\codeception\api\functional\accounts;

use common\models\Account;
use tests\codeception\api\_pages\AccountsRoute;
use tests\codeception\api\functional\_steps\OauthSteps;
use tests\codeception\api\FunctionalTester;

class ChangeUsernameCest {

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
        $account->username = 'Admin';
        $account->save();
    }

    public function testChangeUsername(FunctionalTester $I) {
        $I->wantTo('change my nickname');
        $id = $I->amAuthenticated();

        $this->route->changeUsername($id, 'password_0', 'bruce_wayne');
        $this->assertSuccessResponse($I);
    }

    public function testChangeUsernameNotAvailable(FunctionalTester $I) {
        $I->wantTo('see, that nickname "in use" is not available');
        $id = $I->amAuthenticated();

        $this->route->changeUsername($id, 'password_0', 'Jon');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => false,
            'errors' => [
                'username' => 'error.username_not_available',
            ],
        ]);
    }

    public function testChangeUsernameInternal(OauthSteps $I) {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['change_account_username', 'escape_identity_verification']);
        $I->amBearerAuthenticated($accessToken);

        $this->route->changeUsername(1, null, 'im_batman');
        $this->assertSuccessResponse($I);
    }

    /**
     * @param FunctionalTester $I
     */
    private function assertSuccessResponse(FunctionalTester $I): void {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
