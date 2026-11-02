<?php
declare(strict_types=1);

namespace api\tests\functional\oauth;

use api\tests\FunctionalTester;

final class DiscoveryCest {

    public function authorizationServerMetadata(FunctionalTester $I): void {
        $I->sendGET('/api/.well-known/oauth-authorization-server');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeHttpHeader('Access-Control-Allow-Origin', '*');
        $I->canSeeHttpHeader('X-Accel-Expires', '3600');
        $I->canSeeResponseContainsJson([
            'issuer' => 'http://localhost',
            'authorization_endpoint' => 'http://localhost/oauth2/v1',
            'token_endpoint' => 'http://localhost/api/oauth2/v1/token',
            'device_authorization_endpoint' => 'http://localhost/api/oauth2/v1/devicecode',
            'revocation_endpoint' => 'http://localhost/api/oauth2/v1/revoke',
            'response_types_supported' => ['code'],
            'grant_types_supported' => [
                'authorization_code',
                'refresh_token',
                'client_credentials',
                'urn:ietf:params:oauth:grant-type:device_code',
            ],
            'code_challenge_methods_supported' => ['S256'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic', 'none'],
            'scopes_supported' => [
                'account_info',
                'account_email',
                'minecraft_server_session',
                'offline_access',
                'change_skin',
            ],
            'service_documentation' => 'https://docs.ely.by/en/oauth.html',
        ]);
        $I->cantSeeResponseContainsJson([
            'scopes_supported' => ['block_account'],
        ]);
    }

    public function authorizationServerMetadataWithOrigin(FunctionalTester $I): void {
        $I->haveHttpHeader('Origin', 'https://example.com');
        $I->sendGET('/api/.well-known/oauth-authorization-server');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeHttpHeader('Access-Control-Allow-Origin', '*');
        $I->canSeeResponseJsonMatchesJsonPath('$.issuer');
    }

    public function authorizationServerMetadataPreflight(FunctionalTester $I): void {
        $I->haveHttpHeader('Origin', 'https://example.com');
        $I->haveHttpHeader('Access-Control-Request-Method', 'GET');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'authorization');
        $I->sendOPTIONS('/api/.well-known/oauth-authorization-server');
        $I->canSeeResponseCodeIs(200);
        $I->canSeeHttpHeader('Access-Control-Allow-Origin', '*');
        $I->canSeeHttpHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $I->canSeeHttpHeader('Access-Control-Allow-Headers', 'Authorization');
        $I->canSeeHttpHeader('Access-Control-Max-Age', '86400');
        $I->cantSeeResponseJsonMatchesJsonPath('$.issuer');
    }

}
