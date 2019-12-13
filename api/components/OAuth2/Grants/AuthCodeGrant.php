<?php
declare(strict_types=1);

namespace api\components\OAuth2\Grants;

use api\components\OAuth2\CryptTrait;
use api\components\OAuth2\Events\RequestedRefreshToken;
use api\components\OAuth2\Repositories\PublicScopeRepository;
use DateInterval;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant as BaseAuthCodeGrant;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;
use yii\helpers\StringHelper;

class AuthCodeGrant extends BaseAuthCodeGrant {
    use CryptTrait;

    /**
     * @param DateInterval $accessTokenTTL
     * @param ClientEntityInterface $client
     * @param string|null $userIdentifier
     * @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes
     *
     * @return AccessTokenEntityInterface
     * @throws \League\OAuth2\Server\Exception\OAuthServerException
     * @throws \League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException
     */
    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        $userIdentifier,
        array $scopes = []
    ): AccessTokenEntityInterface {
        foreach ($scopes as $i => $scope) {
            if ($scope->getIdentifier() === PublicScopeRepository::OFFLINE_ACCESS) {
                unset($scopes[$i]);
                $this->getEmitter()->emit(new RequestedRefreshToken());
            }
        }

        return parent::issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);
    }

    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request
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
