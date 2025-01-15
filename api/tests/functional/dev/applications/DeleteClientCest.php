<?php
declare(strict_types=1);

namespace api\tests\functional\dev\applications;

use api\tests\FunctionalTester;

final class DeleteClientCest {

    public function testDelete(FunctionalTester $I): void {
        $I->amAuthenticated('TwoOauthClients');
        $I->sendDELETE('/api/v1/oauth2/first-test-oauth-client');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

}
