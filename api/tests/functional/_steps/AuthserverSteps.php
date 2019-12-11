<?php
declare(strict_types=1);

namespace api\tests\functional\_steps;

use api\tests\FunctionalTester;
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

}
