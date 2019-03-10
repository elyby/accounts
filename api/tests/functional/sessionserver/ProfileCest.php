<?php
namespace api\tests\functional\sessionserver;

use api\tests\_pages\SessionServerRoute;
use api\tests\functional\_steps\SessionServerSteps;
use api\tests\FunctionalTester;
use Faker\Provider\Uuid;

class ProfileCest {

    /**
     * @var SessionServerRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new SessionServerRoute($I);
    }

    public function getProfile(SessionServerSteps $I) {
        $I->wantTo('get info about player textures by uuid');
        $this->route->profile('df936908-b2e1-544d-96f8-2977ec213022');
        $I->canSeeValidTexturesResponse('Admin', 'df936908b2e1544d96f82977ec213022');
    }

    public function getProfileByUuidWithoutDashes(SessionServerSteps $I) {
        $I->wantTo('get info about player textures by uuid without dashes');
        $this->route->profile('df936908b2e1544d96f82977ec213022');
        $I->canSeeValidTexturesResponse('Admin', 'df936908b2e1544d96f82977ec213022');
    }

    public function directCallWithoutUuidPart(FunctionalTester $I) {
        $I->wantTo('call profile route without passing uuid');
        $this->route->profile('');
        $I->canSeeResponseCodeIs(404);
    }

    public function callWithInvalidUuid(FunctionalTester $I) {
        $I->wantTo('call profile route with invalid uuid string');
        $this->route->profile('bla-bla-bla');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Invalid uuid format.',
        ]);
    }

    public function getProfileWithNonexistentUuid(FunctionalTester $I) {
        $I->wantTo('get info about nonexistent uuid');
        $this->route->profile(Uuid::uuid());
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid uuid.',
        ]);
    }

}
