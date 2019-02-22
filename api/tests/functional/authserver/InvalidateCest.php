<?php
namespace api\tests\functional\authserver;

use api\tests\_pages\AuthserverRoute;
use api\tests\functional\_steps\AuthserverSteps;
use Ramsey\Uuid\Uuid;

class InvalidateCest {

    /**
     * @var AuthserverRoute
     */
    private $route;

    public function _before(AuthserverSteps $I) {
        $this->route = new AuthserverRoute($I);
    }

    public function invalidate(AuthserverSteps $I) {
        $I->wantTo('invalidate my token');
        [$accessToken, $clientToken] = $I->amAuthenticated();
        $this->route->invalidate([
            'accessToken' => $accessToken,
            'clientToken' => $clientToken,
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

    public function wrongArguments(AuthserverSteps $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->invalidate([
            'key' => 'value',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function wrongAccessTokenOrClientToken(AuthserverSteps $I) {
        $I->wantTo('invalidate by wrong client and access token');
        $this->route->invalidate([
            'accessToken' => Uuid::uuid4()->toString(),
            'clientToken' => Uuid::uuid4()->toString(),
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseEquals('');
    }

}
