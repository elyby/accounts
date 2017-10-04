<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities\AccessTokenEntity;
use api\components\OAuth2\Entities\RefreshTokenEntity;
use api\components\OAuth2\Utils\Scopes;
use ErrorException;
use League\OAuth2\Server\Entity\AccessTokenEntity as BaseAccessTokenEntity;
use League\OAuth2\Server\Entity\ClientEntity as BaseClientEntity;
use League\OAuth2\Server\Entity\RefreshTokenEntity as BaseRefreshTokenEntity;
use League\OAuth2\Server\Event\ClientAuthenticationFailedEvent;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Util\SecureKey;

class RefreshTokenGrant extends AbstractGrant {

    protected $identifier = 'refresh_token';

    protected $refreshTokenTTL = 604800;

    protected $refreshTokenRotate = false;

    protected $requireClientSecret = true;

    public function setRefreshTokenTTL($refreshTokenTTL): void {
        $this->refreshTokenTTL = $refreshTokenTTL;
    }

    public function getRefreshTokenTTL(): int {
        return $this->refreshTokenTTL;
    }

    public function setRefreshTokenRotation(bool $refreshTokenRotate = true): void {
        $this->refreshTokenRotate = $refreshTokenRotate;
    }

    public function shouldRotateRefreshTokens(): bool {
        return $this->refreshTokenRotate;
    }

    public function setRequireClientSecret(string $required): void {
        $this->requireClientSecret = $required;
    }

    public function shouldRequireClientSecret(): bool {
        return $this->requireClientSecret;
    }

    /**
     * По стандарту OAuth2 scopes должны разделяться пробелом, а не запятой. Косяк.
     * Так что оборачиваем функцию разбора скоупов, заменяя запятые на пробелы.
     *
     * @param string       $scopeParam
     * @param BaseClientEntity $client
     * @param string $redirectUri
     *
     * @return \League\OAuth2\Server\Entity\ScopeEntity[]
     */
    public function validateScopes($scopeParam = '', BaseClientEntity $client, $redirectUri = null) {
        return parent::validateScopes(Scopes::format($scopeParam), $client, $redirectUri);
    }

    /**
     * Метод таки пришлось переписать по той причине, что нынче мы храним access_token в redis с expire значением,
     * так что он может банально несуществовать на тот момент, когда к нему через refresh_token попытаются обратиться.
     * Поэтому мы расширили логику RefreshTokenEntity и она теперь знает о сессии, в рамках которой была создана
     *
     * @inheritdoc
     * @throws \League\OAuth2\Server\Exception\OAuthException
     */
    public function completeFlow(): array {
        $clientId = $this->server->getRequest()->request->get('client_id', $this->server->getRequest()->getUser());
        if ($clientId === null) {
            throw new Exception\InvalidRequestException('client_id');
        }

        $clientSecret = $this->server->getRequest()->request->get(
            'client_secret',
            $this->server->getRequest()->getPassword()
        );
        if ($clientSecret === null && $this->shouldRequireClientSecret()) {
            throw new Exception\InvalidRequestException('client_secret');
        }

        // Validate client ID and client secret
        $client = $this->server->getClientStorage()->get($clientId, $clientSecret, null, $this->getIdentifier());
        if (($client instanceof BaseClientEntity) === false) {
            $this->server->getEventEmitter()->emit(new ClientAuthenticationFailedEvent($this->server->getRequest()));
            throw new Exception\InvalidClientException();
        }

        $oldRefreshTokenParam = $this->server->getRequest()->request->get('refresh_token');
        if ($oldRefreshTokenParam === null) {
            throw new Exception\InvalidRequestException('refresh_token');
        }

        // Validate refresh token
        $oldRefreshToken = $this->server->getRefreshTokenStorage()->get($oldRefreshTokenParam);
        if (($oldRefreshToken instanceof BaseRefreshTokenEntity) === false) {
            throw new Exception\InvalidRefreshException();
        }

        // Ensure the old refresh token hasn't expired
        if ($oldRefreshToken->isExpired()) {
            throw new Exception\InvalidRefreshException();
        }

        /** @var AccessTokenEntity|null $oldAccessToken */
        $oldAccessToken = $oldRefreshToken->getAccessToken();
        if ($oldAccessToken instanceof AccessTokenEntity) {
            // Get the scopes for the original session
            $session = $oldAccessToken->getSession();
        } else {
            if (!$oldRefreshToken instanceof RefreshTokenEntity) {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new ErrorException('oldRefreshToken must be instance of ' . RefreshTokenEntity::class);
            }

            $session = $oldRefreshToken->getSession();
        }

        $scopes = $this->formatScopes($session->getScopes());

        // Get and validate any requested scopes
        $requestedScopesString = $this->server->getRequest()->request->get('scope', '');
        $requestedScopes = $this->validateScopes($requestedScopesString, $client);

        // If no new scopes are requested then give the access token the original session scopes
        if (count($requestedScopes) === 0) {
            $newScopes = $scopes;
        } else {
            // The OAuth spec says that a refreshed access token can have the original scopes or fewer so ensure
            //  the request doesn't include any new scopes
            foreach ($requestedScopes as $requestedScope) {
                if (!isset($scopes[$requestedScope->getId()])) {
                    throw new Exception\InvalidScopeException($requestedScope->getId());
                }
            }

            $newScopes = $requestedScopes;
        }

        // Generate a new access token and assign it the correct sessions
        $newAccessToken = new AccessTokenEntity($this->server);
        $newAccessToken->setId(SecureKey::generate());
        $newAccessToken->setExpireTime($this->getAccessTokenTTL() + time());
        $newAccessToken->setSession($session);

        foreach ($newScopes as $newScope) {
            $newAccessToken->associateScope($newScope);
        }

        // Expire the old token and save the new one
        $oldAccessToken instanceof BaseAccessTokenEntity && $oldAccessToken->expire();
        $newAccessToken->save();

        $this->server->getTokenType()->setSession($session);
        $this->server->getTokenType()->setParam('access_token', $newAccessToken->getId());
        $this->server->getTokenType()->setParam('expires_in', $this->getAccessTokenTTL());

        if ($this->shouldRotateRefreshTokens()) {
            // Expire the old refresh token
            $oldRefreshToken->expire();

            // Generate a new refresh token
            $newRefreshToken = new RefreshTokenEntity($this->server);
            $newRefreshToken->setId(SecureKey::generate());
            $newRefreshToken->setExpireTime($this->getRefreshTokenTTL() + time());
            $newRefreshToken->setAccessToken($newAccessToken);
            $newRefreshToken->save();

            $this->server->getTokenType()->setParam('refresh_token', $newRefreshToken->getId());
        } else {
            $oldRefreshToken->setAccessToken($newAccessToken);
            $oldRefreshToken->save();
        }

        return $this->server->getTokenType()->generateResponse();
    }

}
