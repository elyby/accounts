<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;
use Codeception\Attribute\Examples;
use Codeception\Example;
use Webmozart\Assert\Assert;

final class PkceCest {

    private const string CODE_VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';

    private const string REDIRECT_URI = 'http://127.0.0.1:43210/callback';

    public function desktopApplicationCompleteFlow(FunctionalTester $I): void {
        $authCode = $this->obtainAuthCode($I, 'desktop-app', self::REDIRECT_URI, [
            'code_challenge' => self::buildS256CodeChallenge(self::CODE_VERIFIER),
            'code_challenge_method' => 'S256',
        ]);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'desktop-app',
            'redirect_uri' => self::REDIRECT_URI,
            'code_verifier' => self::CODE_VERIFIER,
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseContainsJson([
            'token_type' => 'Bearer',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
    }

    public function desktopApplicationWithoutCodeChallenge(FunctionalTester $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'desktop-app',
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'state' => 'test-state',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'error_description' => 'Code challenge must be provided for public clients',
            'parameter' => 'code_challenge',
            'redirectUri' => self::REDIRECT_URI . '?state=test-state&error=invalid_request&error_description=Code%20challenge%20must%20be%20provided%20for%20public%20clients&iss=http://localhost',
        ]);
    }

    public function invalidRedirectUriTakesPrecedenceOverPkceError(FunctionalTester $I): void {
        $I->wantToTest('that the error is never redirected to the redirect uri, that is not validated');
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'desktop-app',
            'redirect_uri' => 'https://evil.example/callback',
            'response_type' => 'code',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'parameter' => 'redirect_uri',
        ]);
        $I->cantSeeResponseJsonMatchesJsonPath('$.redirectUri');
    }

    public function invalidCodeChallenge(FunctionalTester $I): void {
        $I->sendGET('/api/oauth2/v1/validate', [
            'client_id' => 'desktop-app',
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'code_challenge' => 'too-short',
            'code_challenge_method' => 'S256',
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'error_description' => 'Code challenge must follow the specifications of RFC-7636.',
            'parameter' => 'code_challenge',
        ]);
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        $I->assertStringStartsWith(self::REDIRECT_URI . '?error=invalid_request&', $redirectUri);
    }

    /**
     * @param Example<array{string|null}> $case
     */
    #[Examples('plain')]
    #[Examples(null)]
    #[Examples('unknown')]
    public function notS256CodeChallengeMethod(FunctionalTester $I, Example $case): void {
        $I->sendGET('/api/oauth2/v1/validate', array_filter([
            'client_id' => 'desktop-app',
            'redirect_uri' => self::REDIRECT_URI,
            'response_type' => 'code',
            'code_challenge' => self::CODE_VERIFIER,
            'code_challenge_method' => $case[0],
        ], fn(?string $value): bool => $value !== null));
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
            'parameter' => 'code_challenge_method',
        ]);
        $I->canSeeResponseJsonMatchesJsonPath('$.error_description');
        [$redirectUri] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        $I->assertStringStartsWith(self::REDIRECT_URI . '?error=invalid_request&', $redirectUri);
    }

    public function exchangeCodeWithInvalidCodeVerifier(FunctionalTester $I): void {
        $authCode = $this->obtainAuthCode($I, 'desktop-app', self::REDIRECT_URI, [
            'code_challenge' => self::buildS256CodeChallenge(self::CODE_VERIFIER),
            'code_challenge_method' => 'S256',
        ]);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'desktop-app',
            'redirect_uri' => self::REDIRECT_URI,
            'code_verifier' => strrev(self::CODE_VERIFIER),
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_grant',
        ]);
    }

    public function exchangeCodeWithoutCodeVerifier(FunctionalTester $I): void {
        $authCode = $this->obtainAuthCode($I, 'desktop-app', self::REDIRECT_URI, [
            'code_challenge' => self::buildS256CodeChallenge(self::CODE_VERIFIER),
            'code_challenge_method' => 'S256',
        ]);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'desktop-app',
            'redirect_uri' => self::REDIRECT_URI,
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
        ]);
    }

    public function webApplicationCompleteFlow(FunctionalTester $I): void {
        $authCode = $this->obtainAuthCode($I, 'ely', 'http://ely.by', [
            'code_challenge' => self::buildS256CodeChallenge(self::CODE_VERIFIER),
            'code_challenge_method' => 'S256',
        ]);

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
            'code_verifier' => self::CODE_VERIFIER,
        ]);
        $I->canSeeResponseCodeIs(200);
        $I->canSeeResponseJsonMatchesJsonPath('$.access_token');
    }

    public function webApplicationPassesCodeVerifierWithoutCodeChallenge(FunctionalTester $I): void {
        $I->wantTo('ensure that PKCE downgrade is not possible');
        $authCode = $this->obtainAuthCode($I, 'ely', 'http://ely.by');

        $I->sendPOST('/api/oauth2/v1/token', [
            'grant_type' => 'authorization_code',
            'code' => $authCode,
            'client_id' => 'ely',
            'client_secret' => 'ZuM1vGchJz-9_UZ5HC3H3Z9Hg5PzdbkM',
            'redirect_uri' => 'http://ely.by',
            'code_verifier' => self::CODE_VERIFIER,
        ]);
        $I->canSeeResponseCodeIs(400);
        $I->canSeeResponseContainsJson([
            'error' => 'invalid_request',
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    private function obtainAuthCode(FunctionalTester $I, string $clientId, string $redirectUri, array $params = []): string {
        $I->amAuthenticated();
        $I->sendPOST('/api/oauth2/v1/complete?' . http_build_query(array_merge([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
        ], $params)), ['accept' => true]);
        $I->canSeeResponseCodeIs(200);
        [$redirect] = $I->grabDataFromResponseByJsonPath('$.redirectUri');
        parse_str((string)parse_url((string)$redirect, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? null;
        Assert::string($code);

        return $code;
    }

    private static function buildS256CodeChallenge(string $codeVerifier): string {
        return rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
    }

}
