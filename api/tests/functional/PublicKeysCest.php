<?php
declare(strict_types=1);

namespace api\tests\functional;

use api\tests\FunctionalTester;

final class PublicKeysCest {

    public function getPublicKeys(FunctionalTester $I): void {
        $I->sendGet('/api/public-keys');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'keys' => [
                [
                    'alg' => 'ES256',
                    'pem' => "-----BEGIN PUBLIC KEY-----\nMFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAES2Pyq9r0CyyviLaWwq0ki5uy8hr/\nZbNO++3j4XP43uLD9/GYkrKGIRl+Hu5HT+LwZvrFcEaVhPk5CvtV4zlYJg==\n-----END PUBLIC KEY-----\n",
                ],
            ],
        ]);
    }

}
