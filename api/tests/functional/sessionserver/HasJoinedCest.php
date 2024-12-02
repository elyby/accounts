<?php
namespace api\tests\functional\sessionserver;

use api\tests\_pages\SessionServerRoute;
use api\tests\functional\_steps\SessionServerSteps;
use api\tests\FunctionalTester;
use function Ramsey\Uuid\v4 as uuid;

class HasJoinedCest {

    private SessionServerRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new SessionServerRoute($I);
    }

    public function hasJoined(SessionServerSteps $I): void {
        $I->wantTo('check hasJoined user to some server');
        [$username, $serverId] = $I->amJoined();

        $this->route->hasJoined([
            'username' => $username,
            'serverId' => $serverId,
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeValidTexturesResponse($username, 'df936908b2e1544d96f82977ec213022', true);
    }

    public function wrongArguments(FunctionalTester $I): void {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->hasJoined([
            'wrong' => 'argument',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'IllegalArgumentException',
            'errorMessage' => 'credentials can not be null.',
        ]);
    }

    public function hasJoinedWithNoJoinOperation(FunctionalTester $I): void {
        $I->wantTo('hasJoined to some server without join call');
        $this->route->hasJoined([
            'username' => 'some-username',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

}
