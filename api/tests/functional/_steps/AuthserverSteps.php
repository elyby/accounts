<?php
declare(strict_types=1);

namespace api\tests\functional\_steps;

use api\tests\FunctionalTester;
use common\models\OauthClient;
use common\models\OauthSession;
use Ramsey\Uuid\Uuid;

class AuthserverSteps extends FunctionalTester {

    public function amAuthenticated(string $asUsername = 'admin', string $password = 'password_0'): array {
        $clientToken = Uuid::uuid4()->toString();
        $this->sendPOST('/api/authserver/authentication/authenticate', [
            'username' => $asUsername,
            'password' => $password,
            'clientToken' => $clientToken,
        ]);

        $accessToken = $this->grabDataFromResponseByJsonPath('$.accessToken')[0];

        return [$accessToken, $clientToken];
    }

    /**
     * Emulates the sessions termination (e.g. on password change) for the tokens issued before the current second
     */
    public function haveMinecraftSessionsRevoked(int $accountId = 1): void {
        // The revocation is compared with the seconds precision, so the tokens issued in the current second
        // wouldn't be considered as revoked if the time() value is used
        OauthSession::updateAll(['revoked_at' => time() + 1], [
            'account_id' => $accountId,
            'client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER,
        ]);
    }

}
