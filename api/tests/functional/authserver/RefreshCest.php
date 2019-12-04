<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Codeception\Example;
use Ramsey\Uuid\Uuid;

class RefreshCest {

    public function refresh(AuthserverSteps $I) {
        $I->wantTo('refresh accessToken');
        [$accessToken, $clientToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => $accessToken,
            'clientToken' => $clientToken,
        ]);
        $this->assertSuccessResponse($I);
    }

    public function refreshLegacyAccessToken(AuthserverSteps $I) {
        $I->wantTo('refresh legacy accessToken');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => 'e7bb6648-2183-4981-9b86-eba5e7f87b42',
            'clientToken' => '6f380440-0c05-47bd-b7c6-d011f1b5308f',
        ]);
        $this->assertSuccessResponse($I);
    }

    /**
     * @example {"accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE1NzU0Nzk1NTMsImV4cCI6MTU3NTY1MjM1MywiZWx5LXNjb3BlcyI6Im1pbmVjcmFmdF9zZXJ2ZXJfc2Vzc2lvbiIsImVseS1jbGllbnQtdG9rZW4iOiJkZWY1MDIwMDE2ZTEzMTBmMzM2YzVjYWQzZDdiMTJmYjcyNmVhYzdlYjgyOGUzMzg1MzBhMmFmODdkZTJhMjRiMTVmNzAxNWQ1MjU1MjhiNGZiMjgzMTgxOTA2ODhlMWE4Njk5MjAwMzBlMTQyZmQ5ZWM5ODBlZDkzMWI1Mzc2MzgyMTliMjVjMjI1MjQyYzdmMjgzMjE0NjcyNDg3ZDQ4MTYxYjMwMGU1MGIzYWJlMTYwYjVkMmE4ZWMyMzMwMGJhMGNlMTg3MzYyYTgyMjJiYjQ4OTU0MzM4MDJiNTBlZDBhYzFhMWUwZDk3NDgxNDciLCJzdWIiOiJlbHl8MSJ9.PuM-8rzj4qtD9l0lUANSIWC8yjJe8ifarOYsAjc3r4iYFt0P6za-gzJEPncDC80oCXsYVlJHtrEypcsB9wJFSg", "clientToken": "d1b1162c-3d73-4b35-b64f-7bf68bd0e853"}
     * @example {"accessToken": "6042634a-a1e2-4aed-866c-c661fe4e63e2", "clientToken": "47fb164a-2332-42c1-8bad-549e67bb210c"}
     */
    public function refreshExpiredToken(AuthserverSteps $I, Example $example) {
        $I->wantTo('refresh legacy accessToken');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => $example['accessToken'],
            'clientToken' => $example['clientToken'],
        ]);
        $this->assertSuccessResponse($I);
    }

    public function wrongArguments(AuthserverSteps $I) {
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

    public function wrongAccessToken(AuthserverSteps $I) {
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

    public function refreshTokenFromBannedUser(AuthserverSteps $I) {
        $I->wantTo('refresh token from suspended account');
        $I->sendPOST('/api/authserver/authentication/refresh', [
            'accessToken' => '918ecb41-616c-40ee-a7d2-0b0ef0d0d732',
            'clientToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'This account has been suspended.',
        ]);
    }

    private function assertSuccessResponse(AuthserverSteps $I) {
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.accessToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.clientToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.id');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.name');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.legacy');
        $I->cantSeeResponseJsonMatchesJsonPath('$.availableProfiles');
    }

}
