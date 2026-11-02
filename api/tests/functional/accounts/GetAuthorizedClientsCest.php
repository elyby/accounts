<?php
declare(strict_types=1);

namespace api\tests\functional\accounts;

use api\tests\FunctionalTester;

class GetAuthorizedClientsCest {

    public function testGet(FunctionalTester $I): void {
        $id = $I->amAuthenticated('admin');
        $I->sendGET("/api/v1/accounts/{$id}/oauth2/authorized");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'test1',
                'name' => 'Test1',
                'description' => 'Some description',
                'scopes' => ['minecraft_server_session', 'account_info'],
                'authorizedAt' => 1479944472,
                'lastUsedAt' => 1479944472,
            ],
        ]);
        // The tokens of this session are revoked, but the client's approval is kept
        $I->canSeeResponseJsonMatchesJsonPath('$.[?(@.id="tlauncher")]');
        // Only the web applications are listed
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.id="unauthorized_minecraft_game_launcher")]');
    }

    public function testGetForNotOwnIdentity(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendGET('/api/v1/accounts/2/oauth2/authorized');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}
