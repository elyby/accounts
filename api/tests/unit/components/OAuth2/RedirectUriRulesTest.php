<?php
declare(strict_types=1);

namespace api\tests\unit\components\OAuth2;

use api\tests\unit\TestCase;
use common\components\OAuth2\RedirectUriRules;

final class RedirectUriRulesTest extends TestCase {

    /**
     * @return iterable<array{string, string, bool}>
     */
    public static function registeredRedirectUris(): iterable {
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
        // Any port is allowed for the loopback redirect uris (RFC 8252 section 7.3)
        yield ['http://127.0.0.1', 'http://127.0.0.1:56228/', true];
        yield ['http://127.0.0.1:8080', 'http://127.0.0.1:56228/callback', true];
        yield ['http://127.0.0.2', 'http://127.0.0.2:56228/', true];
        yield ['http://localhost', 'http://localhost:56228/', true];
        yield ['http://[::1]', 'http://[::1]:56228/', true];
        yield ['my-app://callback', 'my-app://callback', true];

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
        // The loopback exception relaxes only the port
        yield ['http://127.0.0.1', 'http://localhost:56228/', false];
        yield ['http://127.0.0.1', 'https://127.0.0.1:56228/', false];
        yield ['http://127.0.0.1/cb', 'http://127.0.0.1:56228/other', false];
        yield ['http://127.0.0.1', 'http://127.0.0.1.evil.com:56228/', false];
    }

    /**
     * @dataProvider registeredRedirectUris
     */
    public function testIsMatchingRegisteredRedirectUris(string $registeredUri, string $redirectUri, bool $expected): void {
        $this->assertSame($expected, RedirectUriRules::isMatchingRegisteredRedirectUris($redirectUri, [$registeredUri]));
    }

    public function testIsMatchingAnyOfRegisteredRedirectUris(): void {
        $registeredUris = ['http://ely.by', 'https://example.com/cb'];

        $this->assertTrue(RedirectUriRules::isMatchingRegisteredRedirectUris('https://example.com/cb', $registeredUris));
        $this->assertFalse(RedirectUriRules::isMatchingRegisteredRedirectUris('https://evil.com', $registeredUris));
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function redirectUrisWithoutRegisteredOne(): iterable {
        // Allowed
        yield ['https://example.com/callback', true];
        yield ['http://127.0.0.1:56228/', true];
        yield ['elyprismlauncher://oauth/microsoft', true];

        // Denied
        yield ['javascript:alert(1)', false];
        yield ['data:text/html,test', false];
        yield ['file:///etc/passwd', false];
        yield ['https://example.com/callback#fragment', false];
        yield ['https://user@example.com/callback', false];
        yield ['/callback', false];
        yield ['', false];
    }

    /**
     * @dataProvider redirectUrisWithoutRegisteredOne
     */
    public function testIsMatchingWithoutRegisteredRedirectUri(string $redirectUri, bool $expected): void {
        $this->assertSame($expected, RedirectUriRules::isMatchingRegisteredRedirectUris($redirectUri, ['']));
        $this->assertSame($expected, RedirectUriRules::isMatchingRegisteredRedirectUris($redirectUri, []));
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
        yield ['http://127.0.0.2:51234/callback', true];
        yield ['http://app.localhost:51234/callback', true];

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
    public function testIsAllowedForDesktopApplication(string $redirectUri, bool $expected): void {
        $this->assertSame($expected, RedirectUriRules::isAllowedForDesktopApplication($redirectUri));
    }

    /**
     * @return iterable<array{string, bool}>
     */
    public static function webApplicationRedirectUris(): iterable {
        // Allowed
        yield ['http://example.com', true];
        yield ['https://example.com/oauth/ely?param=value', true];
        yield ['https://пример.рф/callback', true];
        yield ['https://127.example.com/callback', true];
        yield ['https://localhost.example.com/callback', true];

        // Denied
        yield ['example.com', false];
        yield ['/callback', false];
        yield ['static_page', false];
        yield ['my-app://callback', false];
        yield ['com.example.app:/callback', false];
        yield ['http://localhost/callback', false];
        yield ['http://LOCALHOST:8080/callback', false];
        yield ['http://app.localhost/callback', false];
        yield ['http://127.0.0.1:8080/callback', false];
        yield ['http://127.0.0.2/callback', false];
        yield ['http://0x7f.1/callback', false];
        yield ['http://[::1]:8080/callback', false];
        yield ['javascript://example.com/%0Aalert(1)', false];
        yield ['file:///etc/passwd', false];
        yield ['https://example.com/callback#fragment', false];
        yield ['https://user:password@example.com/callback', false];
        yield [' https://example.com/callback', false];
        yield ["https://example.com/\ncallback", false];
        yield ['https://example.com\\@evil.com', false];
    }

    /**
     * @dataProvider webApplicationRedirectUris
     */
    public function testCanBeRegisteredAsWebApplication(string $redirectUri, bool $expected): void {
        $this->assertSame($expected, RedirectUriRules::canBeRegisteredAsWebApplication($redirectUri));
    }

}
