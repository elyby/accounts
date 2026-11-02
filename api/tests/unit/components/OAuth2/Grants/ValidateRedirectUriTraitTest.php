<?php
declare(strict_types=1);

namespace api\tests\unit\components\OAuth2\Grants;

use api\tests\unit\TestCase;
use common\components\OAuth2\Entities\ClientEntity;
use common\components\OAuth2\Grants\ValidateRedirectUriTrait;
use common\models\OauthClient;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\EventEmitting\EventEmitter;
use League\OAuth2\Server\Exception\OAuthServerException;

final class ValidateRedirectUriTraitTest extends TestCase {

    /**
     * @return iterable<array{string, string, bool}>
     */
    public static function webApplicationRedirectUris(): iterable {
        // Allowed
        yield ['http://ely.by', 'http://ely.by', true];
        yield ['http://ely.by', 'http://ely.by/', true];
        yield ['http://ely.by', 'http://ely.by/oauth/callback', true];
        yield ['http://ely.by', 'http://ely.by?param=value', true];
        yield ['http://ely.by', 'HTTP://ELY.BY/callback', true];
        yield ['http://ely.by', 'http://ely.by:80/callback', true];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely', true];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely/', true];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely/callback', true];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely?param=value', true];
        yield ['https://example.com/oauth/ely/', 'https://example.com/oauth/ely/callback', true];
        yield ['https://example.com/cb?a=1', 'https://example.com/cb?a=1', true];
        yield ['https://example.com/cb?a=1', 'https://example.com/cb?a=1&b=2', true];
        yield ['https://пример.рф/cb', 'https://xn--e1afmkfd.xn--p1ai/cb/callback', true];
        yield ['https://xn--e1afmkfd.xn--p1ai/cb', 'https://пример.рф/cb/callback', true];
        // The uri is normalized the same way the browser does it, so the validation is made against the actual destination
        yield ['http://ely.by', 'http://ely.by\\@evil.com', true]; // Browser goes to http://ely.by/@evil.com
        yield ['http://ely.by', "http://ely.by/\ncallback", true]; // Browser goes to http://ely.by/callback
        yield ['http://ely.by', ' http://ely.by/callback ', true];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely/sub/../callback', true];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely/%2e/callback', true];

        // Denied
        yield ['http://ely.by', 'http://ely.by.evil.com', false];
        yield ['http://ely.by', 'http://ely.by.evil.com/callback', false];
        yield ['http://ely.by', 'http://ely.by@evil.com', false];
        yield ['http://ely.by', 'http://ely.by:password@evil.com', false];
        yield ['http://ely.by', 'http://ely.by:8080', false];
        yield ['http://ely.by', 'https://ely.by', false];
        yield ['http://ely.by', '//ely.by', false];
        yield ['http://ely.by', 'http://ely.by/#fragment', false];
        yield ['http://ely.by', 'http://ely.by/#', false];
        yield ['http://ely.by', 'http://evil.com\\.ely.by/callback', false];
        yield ['http://ely.by', '', false];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/elyevil', false];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth', false];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely/../../evil', false];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely/%2e%2e/evil', false];
        yield ['https://example.com/oauth/ely', 'https://example.com/oauth/ely%2Fevil', false];
        yield ['https://example.com/cb?a=1', 'https://example.com/cb?a=10', false];
        yield ['https://example.com/cb?a=1', 'https://example.com/cb/other?a=1', false];
    }

    /**
     * @dataProvider webApplicationRedirectUris
     */
    public function testWebApplication(string $registeredUri, string $redirectUri, bool $expected): void {
        $client = new OauthClient();
        $client->type = OauthClient::TYPE_WEB_APPLICATION;
        $this->assertValidation(new ClientEntity('ely', 'Ely.by', $registeredUri, model: $client), $redirectUri, $expected);
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function desktopApplicationRedirectUris(): iterable {
        // Allowed
        yield ['http://localhost', true];
        yield ['http://localhost:51234/callback', true];
        yield ['http://127.0.0.1:51234/callback', true];
        yield ['http://[::1]:51234/callback', true];
        yield ['https://localhost/callback', true];
        yield ['com.example.app:/oauth2redirect', true];
        yield ['my-app://callback', true];
        yield ['http://LOCALHOST:51234/callback', true];
        yield ['http://0x7f.1:51234/callback', true]; // Browser treats it as 127.0.0.1

        // Denied
        yield ['http://example.com/callback', false];
        yield ['http://localhost@evil.com/callback', false];
        yield ['http://localhost.evil.com/callback', false];
        yield ['//evil.com/callback', false];
        yield ['/callback', false];
        yield ['javascript:alert(1)', false];
        yield ['JavaScript:alert(1)', false];
        yield ['data:text/html,test', false];
        yield ['file:///etc/passwd', false];
        yield ['my-app://callback#fragment', false];
        yield ['my-app://user@callback', false];
        yield ['http://localhost.:51234/callback', false];
        yield ['', false];
    }

    /**
     * @dataProvider desktopApplicationRedirectUris
     */
    public function testDesktopApplication(string $redirectUri, bool $expected): void {
        $client = new OauthClient();
        $client->type = OauthClient::TYPE_DESKTOP_APPLICATION;
        $this->assertValidation(new ClientEntity('desktop', 'Desktop', '', model: $client), $redirectUri, $expected);
    }

    private function assertValidation(ClientEntity $client, string $redirectUri, bool $expected): void {
        $validator = new class {
            use ValidateRedirectUriTrait {
                validateRedirectUri as public;
            }

            public function getEmitter(): EventEmitter {
                return new EventEmitter();
            }
        };

        try {
            $validator->validateRedirectUri($redirectUri, $client, new ServerRequest('GET', 'http://localhost'));
            $isValid = true;
        } catch (OAuthServerException $e) {
            $this->assertSame('invalid_client', $e->getErrorType());
            $isValid = false;
        }

        $this->assertSame($expected, $isValid, "Redirect uri \"{$redirectUri}\" validation result is unexpected");
    }

}
