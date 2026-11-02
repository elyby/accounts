<?php
declare(strict_types=1);

namespace api\tests\functional\accounts;

use api\tests\functional\_steps\OauthSteps;
use api\tests\FunctionalTester;
use common\models\OauthSession;

class RevokeAuthorizedClientCest {

    public function testRevokeAuthorizedClient(FunctionalTester $I): void {
        $id = $I->amAuthenticated('admin');
        $I->sendDELETE("/api/v1/accounts/{$id}/oauth2/authorized/test1");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);

        $I->sendGET("/api/v1/accounts/{$id}/oauth2/authorized");
        $I->cantSeeResponseJsonMatchesJsonPath('$.[?(@.id="test1")]');
        $I->assertFalse(OauthSession::find()->andWhere(['account_id' => $id, 'client_id' => 'test1'])->exists());
    }

    public function testTokensStayRevokedAfterReauthorization(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info']);

        $id = $I->amAuthenticated('admin');
        $I->sendDELETE("/api/v1/accounts/{$id}/oauth2/authorized/ely");
        $I->canSeeResponseCodeIs(200);

        $I->wantTo('authorize the client again');
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'account_info',
        ]), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        // Emulate that the session has been created again later than the token was issued
        OauthSession::updateAll(['created_at' => time() + 1], ['account_id' => $id, 'client_id' => 'ely']);

        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/account/v1/info');
        $I->canSeeResponseCodeIs(401);
    }

    public function testRevokeAlreadyRevokedClient(FunctionalTester $I): void {
        $id = $I->amAuthenticated('admin');
        $I->sendDELETE("/api/v1/accounts/{$id}/oauth2/authorized/tlauncher");
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseIsJson();
        $I->canSeeResponseContainsJson([
            'success' => true,
        ]);
    }

    public function testRevokeForNotOwnIdentity(FunctionalTester $I): void {
        $I->amAuthenticated('admin');
        $I->sendDELETE('/api/v1/accounts/2/oauth2/authorized/test1');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeResponseContainsJson([
            'name' => 'Forbidden',
            'message' => 'You are not allowed to perform this action.',
            'code' => 0,
            'status' => 403,
        ]);
    }

}
