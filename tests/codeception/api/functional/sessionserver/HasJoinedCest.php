<?php
namespace tests\codeception\api\functional\sessionserver;

use Faker\Provider\Uuid;
use tests\codeception\api\_pages\SessionServerRoute;
use tests\codeception\api\functional\_steps\SessionServerSteps;
use tests\codeception\api\FunctionalTester;

class HasJoinedCest {

    /**
     * @var SessionServerRoute
     */
    private $route;

    public function _before(FunctionalTester $I) {
        $this->route = new SessionServerRoute($I);
    }

    public function hasJoined(SessionServerSteps $I) {
        $I->wantTo('check hasJoined user to some server');
        list($username, $serverId) = $I->amJoined();

        $this->route->hasJoined([
            'username' => $username,
            'serverId' => $serverId,
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => $username,
            'id' => 'df936908b2e1544d96f82977ec213022',
            'ely' => true,
            'properties' => [
                [
                    'name' => 'textures',
                    'signature' => 'Cg==',
                ],
            ],
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.properties[0].value');
        $value = json_decode($I->grabResponse(), true)['properties'][0]['value'];
        $decoded = json_decode(base64_decode($value), true);
        $I->assertArrayHasKey('timestamp', $decoded);
        $I->assertArrayHasKey('textures', $decoded);
        $I->assertEquals('df936908b2e1544d96f82977ec213022', $decoded['profileId']);
        $I->assertEquals('Admin', $decoded['profileName']);
        $I->assertTrue($decoded['ely']);
        $textures = $decoded['textures'];
        $I->assertArrayHasKey('SKIN', $textures);
        $skinTextures = $textures['SKIN'];
        $I->assertArrayHasKey('url', $skinTextures);
        $I->assertArrayHasKey('hash', $skinTextures);
    }

    public function wrongArguments(FunctionalTester $I) {
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

    public function hasJoinedWithNoJoinOperation(FunctionalTester $I) {
        $I->wantTo('hasJoined to some server without join call');
        $this->route->hasJoined([
            'username' => 'some-username',
            'serverId' => Uuid::uuid(),
        ]);
        $I->seeResponseCodeIs(401);
        $I->seeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'error' => 'ForbiddenOperationException',
            'errorMessage' => 'Invalid token.',
        ]);
    }

}
