<?php
namespace tests\codeception\api\functional\authserver;

use Ramsey\Uuid\Uuid;
use tests\codeception\api\_pages\AuthserverRoute;
use tests\codeception\api\functional\_steps\AuthserverSteps;

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
        list($accessToken, $clientToken) = $I->amAuthenticated();
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
