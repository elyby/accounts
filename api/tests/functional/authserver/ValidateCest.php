<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Ramsey\Uuid\Uuid;

class ValidateCest {

    public function validate(AuthserverSteps $I) {
        $I->wantTo('validate my accessToken');
        [$accessToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => $accessToken,
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function validateLegacyToken(AuthserverSteps $I) {
        $I->wantTo('validate my legacy accessToken');
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => 'e7bb6648-2183-4981-9b86-eba5e7f87b42',
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendPOST('/api/authserver/authentication/validate', [
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
        $I->wantTo('get error on wrong accessToken');
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

    public function expiredAccessToken(AuthserverSteps $I) {
        $I->wantTo('get error on expired accessToken');
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE1NzU0Nzk1NTMsImV4cCI6MTU3NTQ3OTU1MywiZWx5LXNjb3BlcyI6Im1pbmVjcmFmdF9zZXJ2ZXJfc2Vzc2lvbiIsImVseS1jbGllbnQtdG9rZW4iOiJyZW1vdmVkIiwic3ViIjoiZWx5fDEifQ.xDMs5B48nH6p3a1k3WoZKtW4zoNHGGaLD1OGTFte-sUJb2fNMR65LuuBW8DzqO2odgco2xX660zqbhB-tp2OsA',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Token expired.',
        ]);
    }

    public function expiredLegacyAccessToken(AuthserverSteps $I) {
        $I->wantTo('get error on expired legacy accessToken');
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2', // Already expired token from the fixtures
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Token expired.',
        ]);
    }

}
