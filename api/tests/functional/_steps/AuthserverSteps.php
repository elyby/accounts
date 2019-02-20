<?php
declare(strict_types=1);

namespace api\tests\functional\_steps;

use Ramsey\Uuid\Uuid;
use api\tests\_pages\AuthserverRoute;
use api\tests\FunctionalTester;

class AuthserverSteps extends FunctionalTester {

    public function amAuthenticated(string $asUsername = 'admin', string $password = 'password_0'): array {
        $route = new AuthserverRoute($this);
        $clientToken = Uuid::uuid4()->toString();
        $route->authenticate([
            'username' => $asUsername,
            'password' => $password,
            'clientToken' => $clientToken,
        ]);

        $accessToken = $this->grabDataFromResponseByJsonPath('$.accessToken')[0];

        return [$accessToken, $clientToken];
    }

}
