<?php
declare(strict_types=1);

namespace api\tests\functional\dev\applications;

use api\tests\_pages\IdentityInfoRoute;
use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use Yii;

class IdentityInfoCest {

    private IdentityInfoRoute $route;

    public function _before(FunctionalTester $I): void {
        $this->route = new IdentityInfoRoute($I);
    }

    public function testGetErrorIfNoAccessToken(OauthSteps $I): void {
        $I->wantToTest('behavior when this endpoint called without Authorization header');
        $this->route->info();
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Unauthorized',
            'status' => 401,
            'message' => 'Your request was made with invalid credentials.',
        ]);
    }

    public function testGetErrorIfNotEnoughPerms(OauthSteps $I): void {
        $I->wantToTest('behavior when this endpoint called with token, that have not enough scopes');
        $accessToken = $I->getAccessToken();
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'status' => 403,
            'message' => 'You are not allowed to perform this action.',
        ]);
    }

    public function testGetInfoWithTokenContainingPermissionsNames(OauthSteps $I): void {
        $I->wantToTest('that previously issued tokens with the RBAC permissions names are still working');
        $accessToken = Yii::$app->tokens->create([
            'sub' => 'ely|1',
            'client_id' => 'test1',
            'scope' => 'obtain_own_account_info obtain_account_email',
        ])->toString();
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'email' => 'admin@ely.by',
        ]);
    }

    public function testGetInfo(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info']);
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'registeredAt' => 1451775316,
            'profileLink' => 'http://ely.by/u1',
            'preferredLanguage' => 'en',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.email');
    }

    public function testGetInfoWithEmail(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info', 'account_email']);
        $I->amBearerAuthenticated($accessToken);
        $this->route->info();
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
            'username' => 'Admin',
            'registeredAt' => 1451775316,
            'profileLink' => 'http://ely.by/u1',
            'preferredLanguage' => 'en',
            'email' => 'admin@ely.by',
        ]);
    }

}
