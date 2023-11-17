<?php
namespace api\tests\functional\authserver;

use api\tests\_pages\MojangApiRoute;
use api\tests\FunctionalTester;
use Codeception\Example;

class UsernamesToUuidsCest {

    /**
     * @var MojangApiRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new MojangApiRoute($I);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function getUuidByOneUsername(FunctionalTester $I, Example $url) {
        $I->wantTo('get uuid by one username');
        $this->route->uuidsByUsernames($url[0], ['Admin']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function getUuidsByUsernames(FunctionalTester $I, Example $url) {
        $I->wantTo('get uuids by few usernames');
        $this->route->uuidsByUsernames($url[0], ['Admin', 'AccWithOldPassword', 'Notch']);
        $this->validateFewValidUsernames($I);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function getUuidsByUsernamesWithPostString(FunctionalTester $I, Example $url) {
        $I->wantTo('get uuids by few usernames');
        $this->route->uuidsByUsernames($url[0], json_encode(['Admin', 'AccWithOldPassword', 'Notch']));
        $this->validateFewValidUsernames($I);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function getUuidsByPartialNonexistentUsernames(FunctionalTester $I, Example $url) {
        $I->wantTo('get uuids by few usernames and some nonexistent');
        $this->route->uuidsByUsernames($url[0], ['Admin', 'DeletedAccount', 'not-exists-user']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.name="DeletedAccount")]');
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.name="not-exists-user")]');
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function passAllNonexistentUsernames(FunctionalTester $I, Example $url) {
        $I->wantTo('get specific response when pass all nonexistent usernames');
        $this->route->uuidsByUsernames($url[0], ['not-exists-1', 'not-exists-2']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([]);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function passTooManyUsernames(FunctionalTester $I, Example $url) {
        $I->wantTo('get specific response when pass too many usernames');
        $usernames = [];
        for ($i = 0; $i < 150; $i++) {
            $usernames[] = random_bytes(10);
        }

        $this->route->uuidsByUsernames($url[0], $usernames);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Not more that 100 profile name per call is allowed.',
        ]);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function passEmptyUsername(FunctionalTester $I, Example $url) {
        $I->wantTo('get specific response when pass empty username');
        $this->route->uuidsByUsernames($url[0], ['Admin', '']);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'profileName can not be null, empty or array key.',
        ]);
    }

    /**
     * @example ["/api/authlib-injector/api/profiles/minecraft"]
     * @example ["/api/authlib-injector/sessionserver/session/minecraft/profile/lookup/bulk/byname"]
     */
    public function passEmptyField(FunctionalTester $I, Example $url) {
        $I->wantTo('get response when pass empty array');
        $this->route->uuidsByUsernames($url[0], []);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Passed array of profile names is an invalid JSON string.',
        ]);
    }

    private function validateFewValidUsernames(FunctionalTester $I) {
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
            [
                'id' => 'bdc239f08a22518d8b93f02d4827c3eb',
                'name' => 'AccWithOldPassword',
            ],
            [
                'id' => '4aaf4f003b5b4d3692529e8ee0c86679',
                'name' => 'Notch',
            ],
        ]);
    }

}
