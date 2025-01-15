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
use yii\helpers\StringHelper;

trait ValidateRedirectUriTrait {

    abstract public function getEmitter(): EventEmitter;

    /**
     * Override the original method since we need a custom validation logic based on the client type.
     * @inheritDoc
     */
    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request,
    ): void {
        if ($client instanceof ClientEntity && $client->model?->type === OauthClient::TYPE_DESKTOP_APPLICATION) {
            $uri = parse_url($redirectUri);
            if ($uri) {
                // Allow any custom scheme, that is not http
                if ($uri['scheme'] !== 'http' && $uri['scheme'] !== 'https') {
                    return;
                }

                // If it's a http, than should allow only redirection to the local machine
                if (in_array($uri['host'], ['localhost', '127.0.0.1', '[::1]'])) {
                    return;
                }
            }
        } else {
            // The original implementation checks url too strictly (port and path must exactly match).
            // It's nice to have, but we made it this way earlier and so we must keep the same behavior as long as possible
            foreach ((array)$client->getRedirectUri() as $allowedRedirectUri) {
                if (StringHelper::startsWith($redirectUri, $allowedRedirectUri)) {
                    return;
                }
            }
        }

        $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

        throw OAuthServerException::invalidClient($request);
    }

}
