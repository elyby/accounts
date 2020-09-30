<?php
declare(strict_types=1);

namespace api\tests\functional\accounts;

use api\tests\FunctionalTester;

class RevokeAuthorizedClientCest {

    public function testRevokeAuthorizedClient(FunctionalTester $I) {
        $id = $I->amAuthenticated('admin');
        $I->sendDELETE("/api/v1/accounts/{$id}/oauth2/authorized/test1");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);

        $I->sendGET("/api/v1/accounts/{$id}/oauth2/authorized");
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.id="test1")]');
    }

    public function testRevokeAlreadyRevokedClient(FunctionalTester $I) {
        $id = $I->amAuthenticated('admin');
        $I->sendDELETE("/api/v1/accounts/{$id}/oauth2/authorized/tlauncher");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testRevokeForNotOwnIdentity(FunctionalTester $I) {
        $I->amAuthenticated('admin');
        $I->sendDELETE('/api/v1/accounts/2/oauth2/authorized/test1');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}
