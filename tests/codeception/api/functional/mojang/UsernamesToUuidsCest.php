<?php
namespace tests\codeception\api\functional\authserver;

use tests\codeception\api\_pages\MojangApiRoute;
use tests\codeception\api\FunctionalTester;

class UsernamesToUuidsCest {

    /**
     * @var MojangApiRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new MojangApiRoute($I);
    }

    public function geUuidByOneUsername(FunctionalTester $I) {
        $I->wantTo('get uuid by one username');
        $this->route->uuidsByUsernames(['Admin']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
    }

    public function getUuidsByUsernames(FunctionalTester $I) {
        $I->wantTo('get uuids by few usernames');
        $this->route->uuidsByUsernames(['Admin', 'AccWithOldPassword', 'Notch']);
        $this->validateFewValidUsernames($I);
    }

    public function getUuidsByUsernamesWithPostString(FunctionalTester $I) {
        $I->wantTo('get uuids by few usernames');
        $this->route->uuidsByUsernames(json_encode(['Admin', 'AccWithOldPassword', 'Notch']));
        $this->validateFewValidUsernames($I);
    }

    public function getUuidsByPartialNonexistentUsernames(FunctionalTester $I) {
        $I->wantTo('get uuids by few usernames and some nonexistent');
        $this->route->uuidsByUsernames(['Admin', 'not-exists-user']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
    }

    public function passAllNonexistentUsernames(FunctionalTester $I) {
        $I->wantTo('get specific response when pass all nonexistent usernames');
        $this->route->uuidsByUsernames(['not-exists-1', 'not-exists-2']);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([]);
    }

    public function passTooManyUsernames(FunctionalTester $I) {
        $I->wantTo('get specific response when pass too many usernames');
        $usernames = [];
        for($i = 0; $i < 150; $i++) {
            $usernames[] = random_bytes(10);
        }
        $this->route->uuidsByUsernames($usernames);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Not more that 100 profile name per call is allowed.',
        ]);
    }

    public function passEmptyUsername(FunctionalTester $I) {
        $I->wantTo('get specific response when pass empty username');
        $this->route->uuidsByUsernames(['Admin', '']);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'profileName can not be null, empty or array key.',
        ]);
    }

    public function passEmptyField(FunctionalTester $I) {
        $I->wantTo('get response when pass empty array');
        $this->route->uuidsByUsernames([]);
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
