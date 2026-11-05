<?php
declare(strict_types=1);

namespace common\components\OAuth2;

use Uri\WhatWg\Url;

final class RedirectUriRules {

    /**
     * Schemes that are able to execute code or to read local data in the browser
     */
    private const array DANGEROUS_SCHEMES = ['javascript', 'data', 'vbscript', 'file', 'blob', 'about', 'filesystem'];

    /**
     * Desktop applications are allowed to use any custom URI scheme (RFC 8252 section 7.1)
     * or the loopback interface with any port (RFC 8252 section 7.3).
     */
    public static function isAllowedForDesktopApplication(string $redirectUri): bool {
        $url = self::parse($redirectUri);
        if ($url === null) {
            return false;
        }

        $scheme = $url->getScheme();
        if ($scheme === 'http' || $scheme === 'https') {
            return self::isLoopbackHost((string)$url->getAsciiHost());
        }

        return true;
    }

    /**
     * @param array<mixed> $registeredRedirectUris the redirect uris registered for the client
     */
    public static function isMatchingRegisteredRedirectUris(string $redirectUri, array $registeredRedirectUris): bool {
        $url = self::parse($redirectUri);
        if ($url === null) {
            return false;
        }

        $registeredRedirectUris = array_filter($registeredRedirectUris, static fn($uri) => (string)$uri !== '');
        // Such clients can't be created through the UI, but some old launchers are registered as web
        // applications without the redirect uri, so for them any valid redirect uri is allowed
        if ($registeredRedirectUris === []) {
            return true;
        }

        foreach ($registeredRedirectUris as $registeredRedirectUri) {
            if (self::isMatchingRegisteredRedirectUri($url, (string)$registeredRedirectUri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The rules for the uri registered for a web application. Such a client keeps its secret on the server,
     * so the authorization response must get to the server too, not to an application on the user's device.
     * That's why neither custom schemes nor loopback addresses are allowed, native applications
     * should be registered as the desktop ones.
     */
    public static function canBeRegisteredAsWebApplication(string $redirectUri): bool {
        // Control characters, spaces and backslashes: the browser silently removes or normalizes them
        if (preg_match('/[\x00-\x20\\\\]/', $redirectUri) === 1) {
            return false;
        }

        $url = self::parse($redirectUri);
        if ($url === null || !in_array($url->getScheme(), ['http', 'https'], true)) {
            return false;
        }

        return !self::isLoopbackHost((string)$url->getAsciiHost());
    }

    /**
     * The standard requires an exact match with the registered redirect uri (RFC 9700 section 2.1).
     * Historically we allowed any redirect uri, which starts with the registered one,
     * and there are already many clients relying on it, so the comparison is softer:
     * the path and the query can be extended, but the scheme, the host and the port must match exactly
     * and the path can be extended only on the segment boundary.
     */
    private static function isMatchingRegisteredRedirectUri(Url $url, string $registeredRedirectUri): bool {
        $registered = Url::parse($registeredRedirectUri);
        if ($registered === null || $registered->getAsciiHost() === null) {
            return false;
        }

        if ($url->getScheme() !== $registered->getScheme() || $url->getAsciiHost() !== $registered->getAsciiHost()) {
            return false;
        }

        // The default port is normalized to null, so it is handled correctly here.
        // Native apps listen on the loopback interface on an ephemeral port, so for the loopback redirect uris
        // any port is allowed (RFC 8252 section 7.3).
        // New web applications can't be registered with loopback address, but existing launchers must be kept working
        $isLoopback = self::isLoopbackHost((string)$registered->getAsciiHost());
        if (!$isLoopback && $url->getPort() !== $registered->getPort()) {
            return false;
        }

        $path = $url->getPath();
        $registeredPath = rtrim($registered->getPath(), '/');
        if ($path !== $registeredPath && !str_starts_with($path, $registeredPath . '/')) {
            return false;
        }

        $registeredQuery = $registered->getQuery();
        if ($registeredQuery !== null && $registeredQuery !== '') {
            if ($path !== $registered->getPath()) {
                return false;
            }

            $query = $url->getQuery() ?? '';
            if ($query !== $registeredQuery && !str_starts_with($query, $registeredQuery . '&')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Url|null the uri as the browser sees it or null when the uri can't be used as a redirect uri
     */
    private static function parse(string $redirectUri): ?Url {
        $url = Url::parse($redirectUri);
        if ($url === null) {
            return null;
        }

        if (in_array($url->getScheme(), self::DANGEROUS_SCHEMES, true)) {
            return null;
        }

        // The redirection endpoint URI MUST NOT include a fragment component (RFC 6749 section 3.1.2)
        if ($url->getFragment() !== null) {
            return null;
        }

        // There is no legitimate reason to pass credentials within the redirect uri
        if ($url->getUsername() !== null || $url->getPassword() !== null) {
            return null;
        }

        return $url;
    }

    /**
     * The host is already normalized, so any form of the IPv4 address (e.g. 0x7f.1) looks like 127.0.0.1 here.
     */
    private static function isLoopbackHost(string $host): bool {
        return $host === 'localhost'
            || $host === '[::1]'
            || str_ends_with($host, '.localhost')
            || preg_match('/^127\.\d+\.\d+\.\d+$/', $host) === 1;
    }

}
