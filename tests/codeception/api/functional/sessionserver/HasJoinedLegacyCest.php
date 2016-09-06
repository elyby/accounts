<?php
namespace tests\codeception\api\functional\sessionserver;

use Faker\Provider\Uuid;
use tests\codeception\api\_pages\SessionServerRoute;
use tests\codeception\api\functional\_steps\SessionServerSteps;
use tests\codeception\api\FunctionalTester;

class HasJoinedLegacyCest {

    /**
     * @var SessionServerRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new SessionServerRoute($I);
    }

    public function hasJoined(SessionServerSteps $I) {
        $I->wantTo('test hasJoined user to some server by legacy version');
        list($username, $serverId) = $I->amJoined(true);

        $this->route->hasJoinedLegacy([
            'user' => $username,
            'serverId' => $serverId,
        ]);
        $I->seeResponseCodeIs(200);
        $I->canSeeResponseEquals('YES');
    }

    public function wrongArguments(FunctionalTester $I) {
        $I->wantTo('get error on wrong amount of arguments');
        $this->route->hasJoinedLegacy([
            'wrong' => 'argument',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseEquals('credentials can not be null.');
    }

    public function hasJoinedWithNoJoinOperation(FunctionalTester $I) {
        $I->wantTo('hasJoined by legacy version to some server without join call');
        $this->route->hasJoinedLegacy([
            'user' => 'random-username',
            'serverId' => Uuid::uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->canSeeResponseEquals('NO');
    }

}
