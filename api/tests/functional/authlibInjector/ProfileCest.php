<?php
declare(strict_types=1);

namespace api\tests\functional\authlibInjector;

use api\tests\functional\_steps\SessionServerSteps;
use api\tests\FunctionalTester;
use Codeception\Example;
use function Ramsey\Uuid\v4;

class ProfileCest {

    /**
     * @example ["df936908-b2e1-544d-96f8-2977ec213022"]
     * @example ["df936908b2e1544d96f82977ec213022"]
     */
    public function getProfile(SessionServerSteps $I, Example $case): void {
        $I->sendGET("/api/authlib-injector/sessionserver/session/minecraft/profile/{$case[0]}");
        $I->canSeeValidTexturesResponse('Admin', 'df936908b2e1544d96f82977ec213022', false);
    }

    public function getProfileSigned(SessionServerSteps $I): void {
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/profile/df936908b2e1544d96f82977ec213022?unsigned=false');
        $I->canSeeValidTexturesResponse('Admin', 'df936908b2e1544d96f82977ec213022', true);
    }

    public function directCallWithoutUuidPart(FunctionalTester $I): void {
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/profile/');
        $I->canSeeResponseCodeIs(404);
    }

    public function callWithInvalidUuid(FunctionalTester $I): void {
        $I->wantTo('call profile route with invalid uuid string');
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/profile/bla-bla-bla');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'Invalid uuid format.',
        ]);
    }

    public function getProfileWithNonexistentUuid(FunctionalTester $I): void {
        $I->wantTo('get info about nonexistent uuid');
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/profile/' . v4());
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

    public function getProfileOfAccountMarkedForDeletion(FunctionalTester $I): void {
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/profile/6383de63-8f85-4ed5-92b7-5401a1fa68cd');
        $I->canSeeResponseCodeIs(204);
        $I->canSeeResponseEquals('');
    }

}
