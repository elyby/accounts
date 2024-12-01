<?php
declare(strict_types=1);

namespace api\tests\functional\sessionserver;

use api\tests\_pages\SessionServerRoute;
use api\tests\functional\_steps\SessionServerSteps;
use api\tests\FunctionalTester;
use Codeception\Example;
use function Ramsey\Uuid\v4;

class ProfileCest {

    private SessionServerRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new SessionServerRoute($I);
    }

    /**
     * @example ["df936908-b2e1-544d-96f8-2977ec213022"]
     * @example ["df936908b2e1544d96f82977ec213022"]
     */
    public function getProfile(SessionServerSteps $I, Example $case): void {
        $I->wantTo('get info about player textures by uuid');
        $this->route->profile($case[0]);
        $I->canSeeValidTexturesResponse('Admin', 'df936908b2e1544d96f82977ec213022');
    }

    public function getProfileWithSignedTextures(SessionServerSteps $I): void {
        $I->wantTo('get info about player textures by uuid');
        $this->route->profile('df936908b2e1544d96f82977ec213022', true);
        $I->canSeeValidTexturesResponse('Admin', 'df936908b2e1544d96f82977ec213022', true);
    }

    public function getProfileWhichIsNotSynchronized(SessionServerSteps $I): void {
        $I->wantTo('get info about player textures by uuid');
        $this->route->profile('7ff4a9dcd1774ea0ab567f31218004f9', true);

        // Ensure that empty textures was serialized as an empty object
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => '7ff4a9dcd1774ea0ab567f31218004f9',
        ]);
        $texturesValue = $I->grabDataFromResponseByJsonPath('$.properties[0].value')[0];
        $texturesJson = base64_decode($texturesValue);
        $I->assertStringContainsString('"textures":{}', $texturesJson);
    }

    public function directCallWithoutUuidPart(FunctionalTester $I): void {
        $I->wantTo('call profile route without passing uuid');
        $this->route->profile('');
        $I->canSeeResponseCodeIs(404);
    }

    public function callWithInvalidUuid(FunctionalTester $I): void {
        $I->wantTo('call profile route with invalid uuid string');
        $this->route->profile('bla-bla-bla');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Invalid uuid format.',
        ]);
    }

    public function getProfileWithNonexistentUuid(FunctionalTester $I): void {
        $I->wantTo('get info about nonexistent uuid');
        $this->route->profile(v4());
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function getProfileOfAccountMarkedForDeletion(FunctionalTester $I): void {
        $this->route->profile('6383de63-8f85-4ed5-92b7-5401a1fa68cd');
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

}
