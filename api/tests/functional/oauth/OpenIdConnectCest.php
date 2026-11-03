<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\functional\_steps\OauthSteps;
use Carbon\Carbon;
use common\models\OauthSession;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webmozart\Assert\Assert;

final class OpenIdConnectCest {

    private const string CLIENT_SECRET = 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM';

    private const string CODE_VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    public function completeFlow(OauthSteps $I): void {
        $I->wantTo('complete the flow like a relying party: openid + nonce + PKCE, Basic auth on the token endpoint');
        $authCode = $this->obtainAuthCode($I, 'openid profile email', [
            'nonce' => 'n-0S6_WzA2Mj',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', self::CODE_VERIFIER, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);

        $I->haveHttpHeader('Authorization', 'Basic ' . base64_encode('ely:' . self::CLIENT_SECRET));
        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'redirect_uri' => 'http://ely.by',
            'code_verifier' => self::CODE_VERIFIER,
        ]);
        $I->deleteHeader('Authorization');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
            'scope' => 'openid profile email',
        ]);

        [$idTokenString] = $I->grabDataFromResponseByJsonPath('$.id_token');
        [$accessToken] = $I->grabDataFromResponseByJsonPath('$.access_token');
        $idToken = $this->parseAndVerifyWithJwks($I, $idTokenString);

        $claims = $idToken->claims();
        $I->assertSame('http://localhost', $claims->get('iss'));
        $I->assertSame(['ely'], $claims->get('aud'));
        $I->assertSame('1', $claims->get('sub'));
        $I->assertSame('n-0S6_WzA2Mj', $claims->get('nonce'));
        $I->assertSame(3600, $claims->get('exp')->getTimestamp() - $claims->get('iat')->getTimestamp());
        $I->assertSame('Admin', $claims->get('name'));
        $I->assertSame('Admin', $claims->get('preferred_username'));
        $I->assertSame('http://ely.by/u1', $claims->get('profile'));
        $I->assertSame('en', $claims->get('locale'));
        $I->assertSame('admin@ely.by', $claims->get('email'));
        $I->assertTrue($claims->get('email_verified'));
        $I->assertFalse($claims->has('client_id'));
        $I->assertFalse($claims->has('scope'));

        $I->wantTo('get the same subject from the userinfo endpoint');
        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/oauth2/v1/userinfo');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'sub' => '1',
            'name' => 'Admin',
            'preferred_username' => 'Admin',
            'email' => 'admin@ely.by',
            'email_verified' => true,
        ]);
    }

    public function idTokenOnlyWithRequestedClaims(OauthSteps $I): void {
        $idToken = $this->issueIdToken($I, 'openid');
        $I->assertSame('1', $idToken->claims()->get('sub'));
        $I->assertFalse($idToken->claims()->has('nonce'));
        $I->assertFalse($idToken->claims()->has('preferred_username'));
        $I->assertFalse($idToken->claims()->has('email'));
    }

    public function idTokenWithOwnScopes(OauthSteps $I): void {
        $I->wantTo('get the profile and email claims with our own scopes, since the OpenID Connect scopes are their aliases');
        $idToken = $this->issueIdToken($I, 'openid account_info account_email');
        $I->assertSame('Admin', $idToken->claims()->get('preferred_username'));
        $I->assertSame('admin@ely.by', $idToken->claims()->get('email'));
    }

    public function duplicatingScopesAreCollapsed(OauthSteps $I): void {
        $I->wantTo('get only the OpenID Connect scopes when our own scopes with the same permissions are also requested');
        $authCode = $this->obtainAuthCode($I, 'openid account_info profile account_email email');
        $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'scope' => 'openid profile email',
        ]);

        /** @var OauthSession $session */
        $session = OauthSession::findOne(['account_id' => 1, 'client_id' => 'ely']);
        $I->assertSame(['openid', 'profile', 'email'], $session->getScopes());
    }

    public function accessTokenWithOpenIdScopeExpiresInHour(OauthSteps $I): void {
        $authCode = $this->obtainAuthCode($I, 'openid profile');
        $response = $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        $I->assertEqualsWithDelta(3600, $response['expires_in'], 1);
        $I->assertArrayNotHasKey('refresh_token', $response);

        $accessToken = $this->parseToken($response['access_token']);
        $I->assertSame(3600, $accessToken->claims()->get('exp')->getTimestamp() - $accessToken->claims()->get('iat')->getTimestamp());

        $I->wantTo('see that the access token can\'t be used after the expiration');
        Carbon::setTestNow(Carbon::now()->addHour()->addSecond());
        try {
            $I->amBearerAuthenticated($response['access_token']);
            $I->sendGET('/api/oauth2/v1/userinfo');
            $I->canSeeResponseCodeIs(401);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function accessTokenWithOpenIdAndOfflineAccessScopesDoesNotExpire(OauthSteps $I): void {
        $authCode = $this->obtainAuthCode($I, 'openid offline_access profile');
        $response = $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        // See OauthProcess::getToken() for the non-expiring tokens expires_in value
        $I->assertSame(2 ** 31, $response['expires_in'] + time());
        $I->assertSame($response['access_token'], $response['refresh_token']);
        $I->assertFalse($this->parseToken($response['access_token'])->claims()->has('exp'));
    }

    public function accessTokenWithoutOpenIdScopeDoesNotExpire(OauthSteps $I): void {
        $authCode = $this->obtainAuthCode($I, 'profile');
        $response = $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        $I->assertArrayNotHasKey('expires_in', $response);
        $I->assertFalse($this->parseToken($response['access_token'])->claims()->has('exp'));
    }

    public function noIdTokenWithoutOpenIdScope(OauthSteps $I): void {
        $authCode = $this->obtainAuthCode($I, 'profile email', ['nonce' => 'ignored']);
        $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);
        $I->cantSeeResponseJsonMatchesJsonPath('$.id_token');
    }

    public function profileAndEmailScopesGrantAccessToAccountInfo(OauthSteps $I): void {
        $authCode = $this->obtainAuthCode($I, 'profile email');
        $response = $I->issueToken($authCode);

        $I->amBearerAuthenticated($response['access_token']);
        $I->sendGET('/api/account/v1/info');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'id' => 1,
            'email' => 'admin@ely.by',
        ]);
    }

    public function tooLongNonce(OauthSteps $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => 'openid',
            'nonce' => str_repeat('a', 256),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
        ]);
    }

    public function idTokenOnRefreshWithoutNonce(OauthSteps $I): void {
        $authCode = $this->obtainAuthCode($I, 'openid offline_access profile', ['nonce' => 'some-nonce']);
        $response = $I->issueToken($authCode);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $response['refresh_token'],
            'client_id' => 'ely',
            'client_secret' => self::CLIENT_SECRET,
        ]);
        $I->canSeeResponseCodeIs(200);
        [$idTokenString] = $I->grabDataFromResponseByJsonPath('$.id_token');
        $idToken = $this->parseAndVerifyWithJwks($I, $idTokenString);
        // The nonce must not be present in the id_token issued by the refresh (OpenID Connect Core 1.0 section 12.2)
        $I->assertFalse($idToken->claims()->has('nonce'));
        $I->assertSame('1', $idToken->claims()->get('sub'));
    }

    public function userInfoWithPostMethod(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['openid', 'profile']);
        $I->amBearerAuthenticated($accessToken);
        $I->sendPOST('/api/oauth2/v1/userinfo');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'sub' => '1',
            'preferred_username' => 'Admin',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.email');
    }

    public function userInfoWithoutOpenIdScope(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['profile']);
        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/oauth2/v1/userinfo');
        $I->canSeeResponseCodeIs(403);
        $I->canSeeHttpHeader('WWW-Authenticate', 'Bearer error="insufficient_scope", error_description="The access token must contain the `openid` scope."');
        $I->canSeeResponseContainsJson([
            'error' => 'insufficient_scope',
        ]);
    }

    public function userInfoWithRevokedToken(OauthSteps $I): void {
        $accessToken = $I->getAccessToken(['openid']);
        OauthSession::updateAll(['revoked_at' => time() + 1], ['account_id' => 1, 'client_id' => 'ely']);

        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/oauth2/v1/userinfo');
        $I->canSeeResponseCodeIs(401);
    }

    public function userInfoWithClientCredentialsToken(OauthSteps $I): void {
        $accessToken = $I->getAccessTokenByClientCredentialsGrant();
        $I->amBearerAuthenticated($accessToken);
        $I->sendGET('/api/oauth2/v1/userinfo');
        $I->canSeeResponseCodeIs(401);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_token',
        ]);
    }

    public function idTokenCannotBeUsedAsAccessToken(OauthSteps $I): void {
        $idToken = $this->issueIdToken($I, 'openid profile email');

        $I->amBearerAuthenticated($idToken->toString());
        $I->sendGET('/api/oauth2/v1/userinfo');
        $I->canSeeResponseCodeIs(401);

        $I->sendGET('/api/account/v1/info');
        $I->cantSeeResponseCodeIs(200);
    }

    public function openIdScopeOnDeviceCodeFlow(OauthSteps $I): void {
        $I->sendPOST('/api/oauth2/v1/devicecode', [
            'client_id' => 'ely',
            'scope' => 'openid profile',
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'verification_uri' => 'http://localhost/code',
        ]);
    }

    private function issueIdToken(OauthSteps $I, string $scope): UnencryptedToken {
        $authCode = $this->obtainAuthCode($I, $scope);
        $response = $I->issueToken($authCode);
        $I->canSeeResponseCodeIs(200);

        return $this->parseAndVerifyWithJwks($I, $response['id_token']);
    }

    /**
     * @param array<string, string> $params
     */
    private function obtainAuthCode(OauthSteps $I, string $scope, array $params = []): string {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query(array_merge([
            'client_id' => 'ely',
            'redirect_uri' => 'http://ely.by',
            'response_type' => 'code',
            'scope' => $scope,
        ], $params)), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        parse_str((string)parse_url((string)$redirectUri, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? null;
        Assert::string($code);
        $I->deleteHeader('Authorization');

        return $code;
    }

    private function parseToken(string $jwt): UnencryptedToken {
        Assert::stringNotEmpty($jwt);
        $token = (new Parser(new JoseEncoder()))->parse($jwt);
        Assert::isInstanceOf($token, UnencryptedToken::class);

        return $token;
    }

    /**
     * Verifies the token like a relying party: with the key from the JWKS, but not with our private key
     */
    private function parseAndVerifyWithJwks(OauthSteps $I, string $jwt): UnencryptedToken {
        Assert::stringNotEmpty($jwt);
        $token = (new Parser(new JoseEncoder()))->parse($jwt);
        Assert::isInstanceOf($token, UnencryptedToken::class);

        $I->sendGET('/api/.well-known/jwks.json');
        [$keys] = $I->grabDataFromResponseByJsonPath('$.keys');
        $jwk = null;
        foreach ($keys as $key) {
            if ($key['kid'] === $token->headers()->get('kid')) {
                $jwk = $key;
            }
        }

        $I->assertNotNull($jwk, 'The key from the token\'s kid header must be present in the JWKS');
        $I->assertSame('ES256', $token->headers()->get('alg'));

        $publicKey = openssl_pkey_new([
            'ec' => [
                'curve_name' => 'prime256v1',
                'x' => Base64UrlSafe::decode($jwk['x']),
                'y' => Base64UrlSafe::decode($jwk['y']),
            ],
        ]);
        Assert::notFalse($publicKey);
        $details = openssl_pkey_get_details($publicKey);
        Assert::isArray($details);
        $pem = $details['key'];
        $I->assertTrue((new Validator())->validate($token, new SignedWith(new Sha256(), InMemory::plainText($pem))));

        return $token;
    }

}
