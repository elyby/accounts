<?php
declare(strict_types=1);

namespace api\tests\functional\authserver;

use api\tests\functional\_steps\AuthserverSteps;
use Ramsey\Uuid\Uuid;

class InvalidateCest {

    public function invalidate(AuthserverSteps $I): void {
        $I->wantTo('invalidate my token');
        [$accessToken, $clientToken] = $I->amAuthenticated();
        $I->sendPOST('/api/authserver/authentication/invalidate', [
            'accessToken' => $accessToken,
            'clientToken' => $clientToken,
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I): void {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendPOST('/api/authserver/authentication/invalidate', [
            'key' => 'value',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function wrongAccessTokenOrClientToken(AuthserverSteps $I): void {
        $I->wantTo('invalidate by wrong client and access token');
        $I->sendPOST('/api/authserver/authentication/invalidate', [
            'accessToken' => Uuid::uuid4()->toString(),
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

}
