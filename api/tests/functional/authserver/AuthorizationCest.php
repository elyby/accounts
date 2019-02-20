<?php
namespace api\tests\functional\authserver;

use Ramsey\Uuid\Uuid;
use api\tests\_pages\AuthserverRoute;
use api\tests\FunctionalTester;

class AuthorizationCest {

    /**
     * @var AuthserverRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new AuthserverRoute($I);
    }

    public function byName(FunctionalTester $I) {
        $I->wantTo('authenticate by username and password');
        $this->route->authenticate([
            'username' => 'admin',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]);

        $this->testSuccessResponse($I);
    }

    public function byEmail(FunctionalTester $I) {
        $I->wantTo('authenticate by email and password');
        $this->route->authenticate([
            'username' => 'admin@ely.by',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]);

        $this->testSuccessResponse($I);
    }

    public function byNamePassedViaPOSTBody(FunctionalTester $I) {
        $I->wantTo('authenticate by username and password sent via post body');
        $this->route->authenticate(json_encode([
            'username' => 'admin',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]));

        $this->testSuccessResponse($I);
    }

    public function byEmailWithEnabledTwoFactorAuth(FunctionalTester $I) {
        $I->wantTo('get valid error by authenticate account with enabled two factor auth');
        $this->route->authenticate([
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

    public function byEmailWithParamsAsJsonInPostBody(FunctionalTester $I) {
        $I->wantTo('authenticate by email and password, passing values as serialized string in post body');
        $this->route->authenticate(json_encode([
            'username' => 'admin@ely.by',
            'password' => 'password_0',
            'clientToken' => Uuid::uuid4()->toString(),
        ]));

        $this->testSuccessResponse($I);
    }

    public function longClientToken(FunctionalTester $I) {
        $I->wantTo('send non uuid clientToken, but less then 255 characters');
        $this->route->authenticate([
            'username' => 'admin@ely.by',
            'password' => 'password_0',
            'clientToken' => str_pad('', 255, 'x'),
        ]);
        $this->testSuccessResponse($I);
    }

    public function tooLongClientToken(FunctionalTester $I) {
        $I->wantTo('send non uuid clientToken with more then 255 characters length');
        $this->route->authenticate([
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
        $this->route->authenticate([
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
        $this->route->authenticate([
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

    public function bannedAccount(FunctionalTester $I) {
        $I->wantTo('authenticate in suspended account');
        $this->route->authenticate([
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
