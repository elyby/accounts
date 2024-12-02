<?php
declare(strict_types=1);

namespace api\tests\functional\authlibInjector;

use api\tests\functional\_steps\SessionServerSteps;
use api\tests\FunctionalTester;
use function Ramsey\Uuid\v4 as uuid;

class HasJoinedCest {

    public function hasJoined(SessionServerSteps $I): void {
        $I->wantTo('check hasJoined user to some server');
        [$username, $serverId] = $I->amJoined();

        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/hasJoined', [
            'username' => $username,
            'serverId' => $serverId,
        ]);

        $I->seeResponseCodeIs(200);
        $I->canSeeValidTexturesResponse($username, 'df936908b2e1544d96f82977ec213022', true);
    }

    public function wrongArguments(FunctionalTester $I): void {
        $I->wantTo('get error on wrong amount of arguments');
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/hasJoined', [
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
        $I->sendGET('/api/authlib-injector/sessionserver/session/minecraft/hasJoined', [
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
