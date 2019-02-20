<?php
namespace api\tests\functional\authserver;

use Ramsey\Uuid\Uuid;
use api\tests\_pages\AuthserverRoute;
use api\tests\functional\_steps\AuthserverSteps;

class RefreshCest {

    /**
     * @var AuthserverRoute
     */
    private $route;

    public function _before(AuthserverSteps $I) {
        $this->route = new AuthserverRoute($I);
    }

    public function refresh(AuthserverSteps $I) {
        $I->wantTo('refresh my accessToken');
        [$accessToken, $clientToken] = $I->amAuthenticated();
        $this->route->refresh([
            'accessToken' => $accessToken,
            'clientToken' => $clientToken,
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->canSeeResponseJsonMatchesJsonPath('$.accessToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.clientToken');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.id');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.name');
        $I->canSeeResponseJsonMatchesJsonPath('$.selectedProfile.legacy');
        $I->cantSeeResponseJsonMatchesJsonPath('$.availableProfiles');
    }

    public function wrongArguments(AuthserverSteps $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->refresh([
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
        $this->route->refresh([
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
        $this->route->refresh([
            'accessToken' => '918ecb41-616c-40ee-a7d2-0b0ef0d0d732',
            'clientToken' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'This account has been suspended.',
        ]);
    }

}
