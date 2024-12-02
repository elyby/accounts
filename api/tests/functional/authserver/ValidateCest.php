<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Ramsey\Uuid\Uuid;

class ValidateCest {

    public function validate(AuthserverSteps $I): void {
        $I->wantTo('validate my accessToken');
        [$accessToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => $accessToken,
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I): void {
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

    public function wrongAccessToken(AuthserverSteps $I): void {
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

    public function expiredAccessToken(AuthserverSteps $I): void {
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

    public function credentialsFromBannedAccount(AuthserverSteps $I): void {
        $I->wantTo('get error on expired legacy accessToken');
        $I->sendPOST('/api/authserver/authentication/validate', [
            'accessToken' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJFUzI1NiJ9.eyJpYXQiOjE1MTgzMzQ3NDMsImNsaWVudF9pZCI6ImVseSIsInNjb3BlIjoibWluZWNyYWZ0X3NlcnZlcl9zZXNzaW9uIiwic3ViIjoiZWx5fDE1In0.2qla7RzReBi2WtfgP3x8T6ZA0wn9HOrQo57xaZc2wMKPo1Zc49_o6w-5Ku1tbvzmESZfAxNQpfY4EwclEWjHYA',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

}
