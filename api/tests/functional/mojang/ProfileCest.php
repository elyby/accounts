<?php
declare(strict_types=1);

namespace api\tests\functional\mojang;

use api\tests\FunctionalTester;

class ProfileCest {

    public function getProfile(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendGet('/api/mojang/services/minecraft/profile');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 'df936908b2e1544d96f82977ec213022',
            'name' => 'Admin',
            'skins' => [
                [
                    'id' => '1794a784-2d87-32f0-b233-0b2fd5682444',
                    'state' => 'ACTIVE',
                    'url' => 'http://localhost/skin.png',
                    'variant' => 'CLASSIC',
                    'alias' => '',
                ],
            ],
            'capes' => [],
        ]);
    }

    // TODO: add cases for unauthenticated user
    // TODO: add cases for authenticated as a service account user

}
