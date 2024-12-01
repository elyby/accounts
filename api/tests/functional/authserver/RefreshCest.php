<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Codeception\Example;
use Ramsey\Uuid\Uuid;

class RefreshCest {

    /**
     * @example [true]
     * @example [false]
     */
    public function refresh(AuthserverSteps $I, Example $case): void {
        $I->wantTo('refresh accessToken');
        [$accessToken, $clientToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => $accessToken,
            'clientToken' => $clientToken,
            'requestUser' => $case[0],
        ]);
        $this->assertSuccessResponse($I, $case[0]);
    }

    public function refreshWithInvalidClientToken(AuthserverSteps $I): void {
        $I->wantTo('refresh accessToken with not matched client token');
        [$accessToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => $accessToken,
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

    public function refreshExpiredToken(AuthserverSteps $I): void {
        $I->wantTo('refresh legacy accessToken');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE1NzU1NjE1MjgsImV4cCI6MTU3NTU2MTUyOCwiZWx5LXNjb3BlcyI6Im1pbmVjcmFmdF9zZXJ2ZXJfc2Vzc2lvbiIsImVseS1jbGllbnQtdG9rZW4iOiIydnByWnRVdk40VTVtSnZzc0ozaXNpekdVWFhQYnFsV1FsQjVVRWVfUV81bkxKYzlsbUJ3VU1hQWJ1MjBtZC1FNzNtengxNWFsZmRJSU1OMTV5YUpBalZOM29vQW9IRDctOWdOcmciLCJzdWIiOiJlbHl8MSJ9.vwjXzy0VtjJlP6B4RxqoE69yRSBsluZ29VELe4vDi8GCy487eC5cIf9hz9oxp5YcdE7uEJZeqX2yi3nk_0nCaA',
            'clientToken' => '4f368b58-9097-4e56-80b1-f421ae4b53cf',
        ]);
        $this->assertSuccessResponse($I, false);
    }

    public function wrongArguments(AuthserverSteps $I): void {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'key' => 'value',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function wrongAccessToken(AuthserverSteps $I): void {
        $I->wantTo('get error on wrong access or client tokens');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => Uuid::uuid4()->toString(),
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

    public function refreshTokenFromDeletedUser(AuthserverSteps $I): void {
        $I->wantTo('refresh token from account marked for deletion');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => '239ba889-7020-4383-8d99-cd8c8aab4a2f',
            'clientToken' => '47443658-4ff8-45e7-b33e-dc8915ab6421',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

    public function refreshTokenFromBannedUser(AuthserverSteps $I): void {
        $I->wantTo('refresh token from suspended account');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => '918ecb41-616c-40ee-a7d2-0b0ef0d0d732',
            'clientToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

    private function assertSuccessResponse(AuthserverSteps $I, bool $requestUser): void {
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.accessToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.clientToken');
        $I->canSeeResponseContainsJson([
            'selectedProfile' => [
                'id' => 'df936908b2e1544d96f82977ec213022',
                'name' => 'Admin',
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.id');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.name');
        $I->cantSeeResponseJsonMatchesJsonPath('$.availableProfiles');
        if ($requestUser) {
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

}
