<?php
namespace api\tests\functional\authserver;

use api\tests\_pages\MojangApiRoute;
use api\tests\FunctionalTester;
use function Ramsey\Uuid\v4 as uuid;

class UuidToUsernamesHistoryCest {

    /**
     * @var MojangApiRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new MojangApiRoute($I);
    }

    public function getUsernameByUuid(FunctionalTester $I) {
        $I->wantTo('get usernames history by uuid for user, without history');
        $this->route->usernamesByUuid('df936908b2e1544d96f82977ec213022');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'name' => 'Admin',
            ],
        ]);
    }

    public function getUsernameByUuidWithHistory(FunctionalTester $I) {
        $I->wantTo('get usernames history by dashed uuid and expect history with time marks');
        $this->route->usernamesByUuid('d6b3e935-6466-4cb8-86db-b5df91ae6541');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'name' => 'klik202',
            ],
            [
                'name' => 'klik201',
                'changedToAt' => 1474404141000,
            ],
            [
                'name' => 'klik202',
                'changedToAt' => 1474404143000,
            ],
        ]);
    }

    public function passWrongUuid(FunctionalTester $I) {
        $I->wantTo('get user username by some wrong uuid');
        $this->route->usernamesByUuid(uuid());
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function passUuidOfDeletedAccount(FunctionalTester $I) {
        $I->wantTo('get username by passing uuid of the account marked for deleting');
        $this->route->usernamesByUuid('6383de63-8f85-4ed5-92b7-5401a1fa68cd');
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function passWrongUuidFormat(FunctionalTester $I) {
        $I->wantTo('call profile route with invalid uuid string');
        $this->route->usernamesByUuid('bla-bla-bla');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Invalid uuid format.',
        ]);
    }

}
