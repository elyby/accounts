<?php
declare(strict_types=1);

namespace api\tests\functional\mojang;

use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;

final class ProfileCest {

    public function getProfile(FunctionalTester $I): void {
        $I->amAuthenticated();
        $I->sendGet('/api/mojang/services/minecraft/profile');
        $I->canSeeResponseCodeIs(200);
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

    public function getProfileAsServiceAccount(OauthSteps $I): void {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant(['internal_account_info']);
        $I->amBearerAuthenticated($accessToken);

        $I->sendGet('/api/mojang/services/minecraft/profile');
        $I->canSeeResponseCodeIs(404);
        $I->canSeeResponseContainsJson([
            'path' => '/mojang/services/minecraft/profile',
            'errorType' => 'NOT_FOUND',
            'error' => 'NOT_FOUND',
            'errorMessage' => 'The server has not found anything matching the request URI',
            'developerMessage' => 'The server has not found anything matching the request URI',
        ]);
    }

    public function getProfileWithoutAuthentication(FunctionalTester $I): void {
        $I->sendGet('/api/mojang/services/minecraft/profile');
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'path' => '/mojang/services/minecraft/profile',
            'errorType' => 'UnauthorizedOperationException',
            'error' => 'UnauthorizedOperationException',
            'errorMessage' => 'Unauthorized',
            'developerMessage' => 'Unauthorized',
        ]);
    }

}
