<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\EventEmitting\EventEmitter;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;
use yii\helpers\StringHelper;

trait ValidateRedirectUriTrait {

    abstract public function getEmitter(): EventEmitter;

    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request,
    ): void {
        $allowedRedirectUris = (array)$client->getRedirectUri();
        foreach ($allowedRedirectUris as $allowedRedirectUri) {
            if (StringHelper::startsWith($redirectUri, $allowedRedirectUri)) {
                return;
            }
        }

        $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

        throw OAuthServerException::invalidClient($request);
    }

}
