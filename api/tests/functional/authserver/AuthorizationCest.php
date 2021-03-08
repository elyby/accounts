<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\FunctionalTester;
use Codeception\Example;
use OTPHP\TOTP;
use Ramsey\Uuid\Uuid;

class AuthorizationCest {

    /**
     * # Matrix:
     * # * login: username/email
     * # * requestUser: true/false
     * # * json: true/false
     *
     * JSON: false
     * @example {"login": "admin", "password": "password_0"}
     * @example {"login": "admin", "password": "password_0", "requestUser": true}
     * @example {"login": "admin@ely.by", "password": "password_0"}
     * @example {"login": "admin@ely.by", "password": "password_0", "requestUser": true}
     *
     * JSON: true
     * @example {"json": true, "login": "admin", "password": "password_0"}
     * @example {"json": true, "login": "admin", "password": "password_0", "requestUser": true}
     * @example {"json": true, "login": "admin@ely.by", "password": "password_0"}
     * @example {"json": true, "login": "admin@ely.by", "password": "password_0", "requestUser": true}
     */
    public function authenticate(FunctionalTester $I, Example $case) {
        $params = [
            'username' => $case['login'],
            'password' => $case['password'],
            'clientToken' => Uuid::uuid4()->toString(),
        ];
        if ($case['requestUser'] ?? false) {
            $params['requestUser'] = true;
        }

        if ($case['json'] ?? false) {
            $params = json_encode($params);
        }

        $I->sendPOST('/api/authserver/authentication/authenticate', $params);

        $I->canSeeResponseJsonMatchesJsonPath('$.accessToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.clientToken');
        $I->canSeeResponseContainsJson([
            'selectedProfile' => [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
            'availableProfiles' => [
                [
                    'id' => 'df936908b2e1544d96f82977ec213022',
                    'name' => 'Admin',
                ],
            ],
        ]);

        if ($case['requestUser'] ?? false) {
            $I->canSeeResponseContainsJson([
                'user' => [
                    'id' => 'df936908b2e1544d96f82977ec213022',
                    'username' => 'Admin',
                    'properties' => [
                        [
                            'name' => 'preferredLanguage',
                            'value' => 'en',
                        ],
                    ],
                ],
            ]);
        } else {
            $I->cantSeeResponseJsonMatchesJsonPath('$.user');
        }
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

    public function byEmailWithEnabledTwoFactorAuthAndCorrectToken(FunctionalTester $I) {
        $I->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => 'otp@gmail.com',
            'password' => 'password_0:' . TOTP::create('BBBB')->now(),
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'selectedProfile' => [
                'id' => '15d0afa7a2bb44d39f31964cbccc6043',
                'name' => 'AccountWithEnabledOtp',
            ],
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

}
