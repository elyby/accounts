<?php
namespace api\tests\functional\sessionserver;

use api\tests\_pages\SessionServerRoute;
use api\tests\functional\_steps\SessionServerSteps;
use api\tests\FunctionalTester;
use function Ramsey\Uuid\v4 as uuid;

class HasJoinedLegacyCest {

    private SessionServerRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new SessionServerRoute($I);
    }

    public function hasJoined(SessionServerSteps $I): void {
        $I->wantTo('test hasJoined user to some server by legacy version');
        [$username, $serverId] = $I->amJoined(true);

        $this->route->hasJoinedLegacy([
            'user' => $username,
            'serverId' => $serverId,
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('YES');
    }

    public function wrongArguments(FunctionalTester $I): void {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->hasJoinedLegacy([
            'wrong' => 'argument',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseEquals('credentials can not be null.');
    }

    public function hasJoinedWithNoJoinOperation(FunctionalTester $I): void {
        $I->wantTo('hasJoined by legacy version to some server without join call');
        $this->route->hasJoinedLegacy([
            'user' => 'random-username',
            'serverId' => uuid(),
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('NO');
    }

}
