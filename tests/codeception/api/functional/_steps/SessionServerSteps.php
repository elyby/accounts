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

}
