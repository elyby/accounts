<?php
namespace tests\codeception\api\functional\_steps;

use common\models\OauthScope as S;
use Faker\Provider\Uuid;
use tests\codeception\api\_pages\SessionServerRoute;

class SessionServerSteps extends \tests\codeception\api\FunctionalTester {

    public function amJoined($byLegacy = false) {
        $oauthSteps = new OauthSteps($this->scenario);
        $accessToken = $oauthSteps->getAccessToken([S::MINECRAFT_SERVER_SESSION]);
        $route = new SessionServerRoute($this);
        $serverId = Uuid::uuid();
        $username = 'Admin';

        if ($byLegacy) {
            $route->joinLegacy([
                'sessionId' => 'token:' . $accessToken . ':' . 'df936908-b2e1-544d-96f8-2977ec213022',
                'user' => $username,
                'serverId' => $serverId,
            ]);

            $this->canSeeResponseEquals('OK');
        } else {
            $route->join([
                'accessToken' => $accessToken,
                'selectedProfile' => 'df936908-b2e1-544d-96f8-2977ec213022',
                'serverId' => $serverId,
            ]);

            $this->canSeeResponseContainsJson(['id' => 'OK']);
        }

        return [$username, $serverId];
    }
    
    public function canSeeValidTexturesResponse($expectedUsername, $expectedUuid) {
        $this->seeResponseIsJson();
        $this->canSeeResponseContainsJson([
            'name' => $expectedUsername,
            'id' => $expectedUuid,
            'ely' => true,
            'properties' => [
                [
                    'name' => 'textures',
                    'signature' => 'Cg==',
                ],
            ],
        ]);
        $this->canSeeResponseJsonMatchesJsonPath('$.properties[0].value');
        $value = json_decode($this->grabResponse(), true)['properties'][0]['value'];
        $decoded = json_decode(base64_decode($value), true);
        $this->assertArrayHasKey('timestamp', $decoded);
        $this->assertArrayHasKey('textures', $decoded);
        $this->assertEquals($expectedUuid, $decoded['profileId']);
        $this->assertEquals($expectedUsername, $decoded['profileName']);
        $this->assertTrue($decoded['ely']);
        $textures = $decoded['textures'];
        $this->assertArrayHasKey('SKIN', $textures);
        $skinTextures = $textures['SKIN'];
        $this->assertArrayHasKey('url', $skinTextures);
        $this->assertArrayHasKey('hash', $skinTextures);
    }

}
