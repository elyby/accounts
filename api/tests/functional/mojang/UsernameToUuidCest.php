<?php
namespace api\tests\functional\authserver;

use api\tests\_pages\MojangApiRoute;
use api\tests\FunctionalTester;

class UsernameToUuidCest {

    /**
     * @var MojangApiRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new MojangApiRoute($I);
    }

    public function getUuidByUsername(FunctionalTester $I) {
        $I->wantTo('get user uuid by his username');
        $this->route->usernameToUuid('Admin');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'df936908b2e1544d96f82977ec213022',
            'name' => 'Admin',
        ]);
    }

    public function getUuidByUsernameAtMoment(FunctionalTester $I) {
        $I->wantTo('get user uuid by his username at fixed moment');
        $this->route->usernameToUuid('klik201', 1474404142);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'd6b3e93564664cb886dbb5df91ae6541',
            'name' => 'klik202',
        ]);
    }

    public function getUuidByUsernameAtWrongMoment(FunctionalTester $I) {
        $I->wantTo('get 204 if passed once used, but changed username at moment, when it was changed');
        $this->route->usernameToUuid('klik201', 1474404144);
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function getUuidByUsernameWithoutMoment(FunctionalTester $I) {
        $I->wantTo('get 204 if username not busy and not passed valid time mark, when it was busy');
        $this->route->usernameToUuid('klik201');
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function getUuidByWrongUsername(FunctionalTester $I) {
        $I->wantTo('get user uuid by some wrong username');
        $this->route->usernameToUuid('not-exists-user');
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function getUuidForDeletedAccount(FunctionalTester $I) {
        $I->wantTo('get uuid for account that marked for deleting');
        $this->route->usernameToUuid('DeletedAccount');
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function nonPassedUsername(FunctionalTester $I) {
        $I->wantTo('get 404 on not passed username');
        $this->route->usernameToUuid('');
        $I->canSeeResponseCodeIs(404);
    }

}
