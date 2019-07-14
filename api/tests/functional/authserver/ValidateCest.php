<?php
namespace api\tests\functional\authserver;

use api\tests\_pages\AuthserverRoute;
use api\tests\functional\_steps\AuthserverSteps;
use Ramsey\Uuid\Uuid;

class ValidateCest {

    /**
     * @var AuthserverRoute
     */
    private $route;

    public function _before(AuthserverSteps $I) {
        $this->route = new AuthserverRoute($I);
    }

    public function validate(AuthserverSteps $I) {
        $I->wantTo('validate my accessToken');
        [$accessToken] = $I->amAuthenticated();
        $this->route->validate([
            'accessToken' => $accessToken,
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->validate([
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
        $this->route->validate([
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
        $this->route->validate([
            // Knowingly expired token from the dump
            'accessToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Token expired.',
        ]);
    }

}
