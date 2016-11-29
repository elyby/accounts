<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities;
use ErrorException;
use League\OAuth2\Server\Entity\ClientEntity as OriginalClientEntity;
use League\OAuth2\Server\Entity\RefreshTokenEntity as OriginalRefreshTokenEntity;
use League\OAuth2\Server\Event;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Util\SecureKey;

class RefreshTokenGrant extends \League\OAuth2\Server\Grant\RefreshTokenGrant {

    public $refreshTokenRotate = false;

    protected function createAccessTokenEntity() {
        return new Entities\AccessTokenEntity($this->server);
    }

    protected function createRefreshTokenEntity() {
        return new Entities\RefreshTokenEntity($this->server);
    }

    protected function createSessionEntity() {
        return new Entities\SessionEntity($this->server);
    }

    /**
     * Метод таки пришлось переписать по той причине, что нынче мы храним access_token в redis с expire значением,
     * так что он может банально несуществовать на тот момент, когда к нему через refresh_token попытаются обратиться.
     * Поэтому мы расширили логику RefreshTokenEntity и она теперь знает о сессии, в рамках которой была создана
     *
     * @inheritdoc
     */
    public function completeFlow() {
        $clientId = $this->server->getRequest()->request->get('client_id', $this->server->getRequest()->getUser());
        if (is_null($clientId)) {
            throw new Exception\InvalidRequestException('client_id');
        }

        $clientSecret = $this->server->getRequest()->request->get(
            'client_secret',
            $this->server->getRequest()->getPassword()
        );
        if ($this->shouldRequireClientSecret() && is_null($clientSecret)) {
            throw new Exception\InvalidRequestException('client_secret');
        }

        // Validate client ID and client secret
        $client = $this->server->getClientStorage()->get(
            $clientId,
            $clientSecret,
            null,
            $this->getIdentifier()
        );

        if (($client instanceof OriginalClientEntity) === false) {
            $this->server->getEventEmitter()->emit(new Event\ClientAuthenticationFailedEvent($this->server->getRequest()));
            throw new Exception\InvalidClientException();
        }

        $oldRefreshTokenParam = $this->server->getRequest()->request->get('refresh_token', null);
        if ($oldRefreshTokenParam === null) {
            throw new Exception\InvalidRequestException('refresh_token');
        }

        // Validate refresh token
        $oldRefreshToken = $this->server->getRefreshTokenStorage()->get($oldRefreshTokenParam);
        if (($oldRefreshToken instanceof OriginalRefreshTokenEntity) === false) {
            throw new Exception\InvalidRefreshException();
        }

        // Ensure the old refresh token hasn't expired
        if ($oldRefreshToken->isExpired()) {
            throw new Exception\InvalidRefreshException();
        }

        /** @var Entities\AccessTokenEntity|null $oldAccessToken */
        $oldAccessToken = $oldRefreshToken->getAccessToken();
        if ($oldAccessToken instanceof Entities\AccessTokenEntity) {
            // Get the scopes for the original session
            $session = $oldAccessToken->getSession();
        } else {
            if (!$oldRefreshToken instanceof Entities\RefreshTokenEntity) {
                throw new ErrorException('oldRefreshToken must be instance of ' . Entities\RefreshTokenEntity::class);
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
        $newAccessToken = $this->createAccessTokenEntity();
        $newAccessToken->setId(SecureKey::generate());
        $newAccessToken->setExpireTime($this->getAccessTokenTTL() + time());
        $newAccessToken->setSession($session);

        foreach ($newScopes as $newScope) {
            $newAccessToken->associateScope($newScope);
        }

        // Expire the old token and save the new one
        ($oldAccessToken instanceof Entities\AccessTokenEntity) && $oldAccessToken->expire();
        $newAccessToken->save();

        $this->server->getTokenType()->setSession($session);
        $this->server->getTokenType()->setParam('access_token', $newAccessToken->getId());
        $this->server->getTokenType()->setParam('expires_in', $this->getAccessTokenTTL());

        if ($this->shouldRotateRefreshTokens()) {
            // Expire the old refresh token
            $oldRefreshToken->expire();

            // Generate a new refresh token
            $newRefreshToken = $this->createRefreshTokenEntity();
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
