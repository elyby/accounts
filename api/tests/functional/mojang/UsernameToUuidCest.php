<?php
namespace api\tests\functional\mojang;

use api\tests\FunctionalTester;
use Codeception\Example;

final class UsernameToUuidCest {

    public static function endpoints(): array {
        return [
            ['/api/mojang/profiles'],
            ['/api/mojang/services/minecraft/profile/lookup/name'],
        ];
    }

    /**
     * @dataProvider endpoints
     */
    public function getUuidByUsername(FunctionalTester $I, Example $url): void {
        $I->wantTo('get user uuid by his username');
        $I->sendGET("{$url[0]}/Admin");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'df936908b2e1544d96f82977ec213022',
            'name' => 'Admin',
        ]);
    }

    /**
     * @dataProvider endpoints
     */
    public function getUuidByUsernameAtMoment(FunctionalTester $I, Example $url): void {
        $I->wantTo('get user uuid by his username at fixed moment');
        $I->sendGET("{$url[0]}/klik201", ['at' => 1474404142]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'd6b3e93564664cb886dbb5df91ae6541',
            'name' => 'klik202',
        ]);
    }

    /**
     * @dataProvider endpoints
     */
    public function getUuidByUsernameAtWrongMoment(FunctionalTester $I, Example $url): void {
        $I->wantTo('get 204 if passed once used, but changed username at moment, when it was changed');
        $I->sendGET("{$url[0]}/klik201", ['at' => 1474404144]);
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => "{$url[0]}/klik201?at=1474404144",
            'errorMessage' => "Couldn't find any profile with name klik201",
        ]);
    }

    /**
     * @dataProvider endpoints
     */
    public function getUuidByUsernameWithoutMoment(FunctionalTester $I, Example $url): void {
        $I->wantTo('get 204 if username not busy and not passed valid time mark, when it was busy');
        $I->sendGET("{$url[0]}/klik201");
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => "{$url[0]}/klik201",
            'errorMessage' => "Couldn't find any profile with name klik201",
        ]);
    }

    /**
     * @dataProvider endpoints
     */
    public function getUuidByWrongUsername(FunctionalTester $I, Example $url): void {
        $I->wantTo('get user uuid by some wrong username');
        $I->sendGET("{$url[0]}/not-exists-user");
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => "{$url[0]}/not-exists-user",
            'errorMessage' => "Couldn't find any profile with name not-exists-user",
        ]);
    }

    /**
     * @dataProvider endpoints
     */
    public function getUuidForDeletedAccount(FunctionalTester $I, Example $url): void {
        $I->wantTo('get uuid for account that marked for deleting');
        $I->sendGET("{$url[0]}/DeletedAccount");
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => "{$url[0]}/DeletedAccount",
            'errorMessage' => "Couldn't find any profile with name DeletedAccount",
        ]);
    }

    public function legacyNonPassedUsername(FunctionalTester $I): void {
        $I->wantTo('get 404 if no username is passed on old endpoint');
        $I->sendGET('/api/mojang/profiles');
        $I->canSeeResponseCodeIs(404);
    }

    public function getUuidForIncompletePath(FunctionalTester $I): void {
        $I->sendGET('/api/mojang/services/minecraft/profile/lookup/name');
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'path' => '/api/mojang/services/minecraft/profile/lookup/name',
            'error' => 'CONSTRAINT_VIOLATION',
            'errorMessage' => 'Invalid UUID string: name',
        ]);
    }

}
