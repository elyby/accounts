<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\Entities\ClientEntity;
use common\models\OauthClient;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\EventEmitting\EventEmitter;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;
use Uri\WhatWg\Url;

trait ValidateRedirectUriTrait {

    private const array LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

    abstract public function getEmitter(): EventEmitter;

    /**
     * Override the original method since we need a custom validation logic based on the client type.
     *
     * @inheritDoc
     */
    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request,
    ): void {
        $url = self::parseRedirectUri($redirectUri);
        if ($url !== null) {
            if ($client instanceof ClientEntity && $client->model?->type === OauthClient::TYPE_DESKTOP_APPLICATION) {
                if (self::isAllowedDesktopRedirectUri($url)) {
                    return;
                }
            } else {
                foreach ((array)$client->getRedirectUri() as $allowedRedirectUri) {
                    if (self::isMatchingRegisteredRedirectUri($url, (string)$allowedRedirectUri)) {
                        return;
                    }
                }
            }
        }

        $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

        // The library returns invalid_client here, which is indistinguishable from the unknown client_id.
        // The invalid redirect uri is the invalid parameter value, so invalid_request is returned instead.
        // The error must not be redirected to the invalid redirect uri (RFC 6749 section 4.1.2.1)
        // and the registered redirect uris aren't disclosed. The hint keeps the "Check the `X` parameter" form,
        // so the parameter can be extracted from it (see OauthProcess::buildCompleteErrorResponse()).
        throw OAuthServerException::invalidRequest(
            'redirect_uri',
            'Check the `redirect_uri` parameter: it doesn\'t match the redirect uri registered for the client.',
        );
    }

    private static function parseRedirectUri(string $redirectUri): ?Url {
        $url = Url::parse($redirectUri);
        if ($url === null) {
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
     * Desktop applications are allowed to use any custom URI scheme (RFC 8252 section 7.1)
     * or the loopback interface with any port (RFC 8252 section 7.3).
     */
    private static function isAllowedDesktopRedirectUri(Url $url): bool {
        $scheme = $url->getScheme();
        if ($scheme === 'http' || $scheme === 'https') {
            return in_array($url->getAsciiHost(), self::LOOPBACK_HOSTS, true);
        }

        // Schemes that are able to execute code or to read local data in the browser
        return !in_array($scheme, ['javascript', 'data', 'vbscript', 'file', 'blob', 'about', 'filesystem'], true);
    }

    /**
     * The original library implementation requires an exact match. Historically we allowed any redirect uri,
     * which starts with the registered one, so to keep the compatibility we still allow to extend the registered
     * path and query, but the scheme, the host and the port must match exactly and the path can be extended
     * only on the segment boundary.
     */
    private static function isMatchingRegisteredRedirectUri(Url $url, string $registeredRedirectUri): bool {
        $registered = Url::parse($registeredRedirectUri);
        if ($registered === null || $registered->getAsciiHost() === null) {
            return false;
        }

        // The default port is normalized to null, so it is handled correctly here.
        // Native apps listen on the loopback interface on an ephemeral port, so for the loopback redirect uris
        // any port is allowed (RFC 8252 section 7.3). The launchers, registered as web applications, rely on it.
        $isLoopback = in_array($registered->getAsciiHost(), self::LOOPBACK_HOSTS, true);
        if ($url->getScheme() !== $registered->getScheme()
         || $url->getAsciiHost() !== $registered->getAsciiHost()
         || (!$isLoopback && $url->getPort() !== $registered->getPort())
        ) {
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

}
