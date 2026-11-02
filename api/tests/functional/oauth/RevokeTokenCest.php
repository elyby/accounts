<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\functional\_steps\OauthSteps;
use common\models\OauthSession;
use Yii;

final class RevokeTokenCest {

    private const string CLIENT_SECRET = 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM';

    public function revokeToken(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info']);
        $this->assertTokenIsValid($I, $accessToken);

        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => $accessToken,
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(200);

        $this->assertTokenIsRevoked($I, $accessToken);
        $I->wantTo('ensure that the client\'s approval is kept');
        $I->assertTrue(OauthSession::find()->andWhere(['account_id' => 1, 'client_id' => 'ely'])->exists());
    }

    public function revokeRefreshToken(OauthSteps $I): void {
        $refreshToken = $I->getRefreshToken(['account_info']);

        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => $refreshToken,
            'token_type_hint' => 'refresh_token',
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(200);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_grant',
        ]);
    }

    public function revokeTokenWithBasicAuth(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info']);

        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('ely:' . self::CLIENT_SECRET));
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => $accessToken,
        ]);
        $I->canSeeResponseCodeIs(200);

        $I->deleteHeader('Authorization');
        $this->assertTokenIsRevoked($I, $accessToken);
    }

    public function revokeTokenOfAnotherClient(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info']);

        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => $accessToken,
            'client_id' => 'test1',
            'client_secret' => 'eEvrKHF47sqiaX94HsX-xXzdGiz3mcsq',
        ]);
        $I->canSeeResponseCodeIs(200);

        $this->assertTokenIsValid($I, $accessToken);
    }

    public function revokeAlreadyRevokedToken(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['account_info']);
        $revokedAt = time() + 100;
        OauthSession::updateAll(['revoked_at' => $revokedAt], ['account_id' => 1, 'client_id' => 'ely']);

        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => $accessToken,
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(200);

        $I->wantTo('ensure that the already revoked token doesn\'t revoke the tokens issued after its revocation');
        /** @var OauthSession $session */
        $session = OauthSession::findOne(['account_id' => 1, 'client_id' => 'ely']);
        $I->assertSame($revokedAt, $session->revoked_at);
    }

    public function revokeInvalidToken(OauthSteps $I): void {
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => 'some-invalid-token',
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(200);
    }

    public function revokeTokenWithoutAccount(OauthSteps $I): void {
        $I->wantTo('revoke the client_credentials token, which isn\'t related to any account');
        $accessToken = Yii::$app->tokens->create(['client_id' => 'ely'])->toString();
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => $accessToken,
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(200);
    }

    public function revokeByDesktopApplicationWithoutSecret(OauthSteps $I): void {
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => 'some-invalid-token',
            'client_id' => 'desktop-app',
        ]);
        $I->canSeeResponseCodeIs(200);
    }

    public function revokeWithoutClientSecret(OauthSteps $I): void {
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => 'some-invalid-token',
            'client_id' => 'ely',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function revokeWithInvalidClientSecret(OauthSteps $I): void {
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'token' => 'some-invalid-token',
            'client_id' => 'ely',
            'client_secret' => 'invalid',
        ]);
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_client',
        ]);
    }

    public function revokeWithoutToken(OauthSteps $I): void {
        $I->sendPOST('/api/oauth2/v1/revoke', [
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
        ]);
    }

    private function assertTokenIsValid(OauthSteps $I, string $accessToken): void {
        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/account/v1/info');
        $I->canSeeResponseCodeIs(200);
        $I->deleteHeader('Authorization');
    }

    private function assertTokenIsRevoked(OauthSteps $I, string $accessToken): void {
        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/account/v1/info');
        $I->canSeeResponseCodeIs(401);
        $I->deleteHeader('Authorization');
    }

}
