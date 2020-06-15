<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\FunctionalTester;
use Codeception\Example;
use Ramsey\Uuid\Uuid;

class AuthorizationCest {

    /**
     * @example {"login": "admin", "password": "password_0"}
     * @example {"login": "admin@ely.by", "password": "password_0"}
     */
    public function byFormParamsPostRequest(FunctionalTester $I, Example $example) {
        $I->wantTo('authenticate by username and password');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => $example['login'],
            'password' => $example['password'],
            'clientToken' => Uuid::uuid4()->toString(),
        ]);

        $this->testSuccessResponse($I);
    }

    /**
     * @example {"login": "admin", "password": "password_0"}
     * @example {"login": "admin@ely.by", "password": "password_0"}
     */
    public function byJsonPostRequest(FunctionalTester $I, Example $example) {
        $I->wantTo('authenticate by username and password sent via post body');
        $I->sendPOST('/api/authserver/authentication/authenticate', json_encode([
            'username' => $example['login'],
            'password' => $example['password'],
            'clientToken' => Uuid::uuid4()->toString(),
        ]));

        $this->testSuccessResponse($I);
    }

    public function byEmailWithEnabledTwoFactorAuth(FunctionalTester $I) {
        $I->wantTo('get valid error by authenticate account with enabled two factor auth');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'otp@gmail.com',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Account protected with two factor auth.',
        ]);
    }

    public function tooLongClientToken(FunctionalTester $I) {
        $I->wantTo('send non uuid clientToken with more then 255 characters length');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'admin@ely.by',
            'password' => 'password_0',
            'clientToken' => str_pad('', 256, 'x'),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'clientToken is too long.',
        ]);
    }

    public function wrongArguments(FunctionalTester $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'key' => 'value',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function wrongNicknameAndPassword(FunctionalTester $I) {
        $I->wantTo('authenticate by username and password with wrong data');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'nonexistent_user',
            'password' => 'nonexistent_password',
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid credentials. Invalid nickname or password.',
        ]);
    }

    public function deletedAccount(FunctionalTester $I) {
        $I->wantTo('authenticate in account marked for deletion');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'DeletedAccount',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid credentials. Invalid nickname or password.',
        ]);
    }

    public function bannedAccount(FunctionalTester $I) {
        $I->wantTo('authenticate in suspended account');
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'Banned',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'This account has been suspended.',
        ]);
    }

    private function testSuccessResponse(FunctionalTester $I) {
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.accessToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.clientToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.availableProfiles[0].id');
        $I->canSeeResponseJsonMatchesJsonPath('$.availableProfiles[0].name');
        $I->canSeeResponseJsonMatchesJsonPath('$.availableProfiles[0].legacy');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.id');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.name');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.legacy');
    }

}
