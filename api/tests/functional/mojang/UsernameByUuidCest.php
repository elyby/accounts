<?php
declare(strict_types=1);

namespace api\tests\functional\mojang;

use api\tests\FunctionalTester;

final class UsernameByUuidCest {

    public function getUsernameByUuid(FunctionalTester $I): void {
        $I->sendGET('/api/mojang/services/minecraft/profile/lookup/df936908b2e1544d96f82977ec213022');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'df936908b2e1544d96f82977ec213022',
            'name' => 'Admin',
        ]);
    }

    public function getUsernameByInvalidUuid(FunctionalTester $I): void {
        $I->sendGET('/api/mojang/services/minecraft/profile/lookup/123ABC');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => '/api/mojang/services/minecraft/profile/lookup/123ABC',
            'error' => 'CONSTRAINT_VIOLATION',
            'errorMessage' => 'Invalid UUID string: 123ABC',
        ]);
    }

    public function getUsernameByWrongUuid(FunctionalTester $I): void {
        $I->sendGET('/api/mojang/services/minecraft/profile/lookup/644b25a8-1b0e-46a8-ad2a-97b53ecbb0a2');
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => '/api/mojang/services/minecraft/profile/lookup/644b25a8-1b0e-46a8-ad2a-97b53ecbb0a2',
            'error' => 'NOT_FOUND',
            'errorMessage' => 'Not Found',
        ]);
    }

    public function getUuidForDeletedAccount(FunctionalTester $I): void {
        $I->sendGET('/api/mojang/services/minecraft/profile/lookup/6383de63-8f85-4ed5-92b7-5401a1fa68cd');
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => '/api/mojang/services/minecraft/profile/lookup/6383de63-8f85-4ed5-92b7-5401a1fa68cd',
            'error' => 'NOT_FOUND',
            'errorMessage' => 'Not Found',
        ]);
    }

    public function nonPassedUuid(FunctionalTester $I): void {
        $I->sendGET('/api/mojang/services/minecraft/profile/lookup/');
        $I->canSeeResponseCodeIs(404);
    }

}
