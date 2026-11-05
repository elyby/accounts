<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\Entities\ClientEntity;
use common\components\OAuth2\Exceptions\AuthorizationRequestException;
use common\components\OAuth2\RedirectUriRules;
use common\models\OauthClient;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\EventEmitting\EventEmitter;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;

trait ValidateRedirectUriTrait {

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
        if ($client instanceof ClientEntity && $client->model?->type === OauthClient::TYPE_DESKTOP_APPLICATION) {
            $isAllowed = RedirectUriRules::isAllowedForDesktopApplication($redirectUri);
        } else {
            $isAllowed = RedirectUriRules::isMatchingRegisteredRedirectUris($redirectUri, (array)$client->getRedirectUri());
        }

        if ($isAllowed) {
            return;
        }

        $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

        // The library returns invalid_client here, which is indistinguishable from the unknown client_id.
        // The invalid redirect uri is the invalid parameter value, so invalid_request is returned instead.
        // The error must not be redirected to the invalid redirect uri (RFC 6749 section 4.1.2.1)
        // and the registered redirect uris aren't disclosed.
        throw AuthorizationRequestException::invalidParameter(
            'redirect_uri',
            'The `redirect_uri` parameter doesn\'t match the redirect uri registered for the client.',
        );
    }

}
