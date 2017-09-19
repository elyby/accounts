<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities\AccessTokenEntity;
use api\components\OAuth2\Entities\SessionEntity;
use League\OAuth2\Server\Entity\ClientEntity as BaseClientEntity;
use League\OAuth2\Server\Event;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Util\SecureKey;

class ClientCredentialsGrant extends AbstractGrant {

    protected $identifier = 'client_credentials';

    /**
     * @return array
     * @throws \League\OAuth2\Server\Exception\OAuthException
     */
    public function completeFlow(): array {
        $clientId = $this->server->getRequest()->request->get('client_id', $this->server->getRequest()->getUser());
        if ($clientId === null) {
            throw new Exception\InvalidRequestException('client_id');
        }

        $clientSecret = $this->server->getRequest()->request->get('client_secret');
        if ($clientSecret === null) {
            throw new Exception\InvalidRequestException('client_secret');
        }

        // Validate client ID and client secret
        $client = $this->server->getClientStorage()->get($clientId, $clientSecret, null, $this->getIdentifier());
        if (!$client instanceof BaseClientEntity) {
            $this->server->getEventEmitter()->emit(new Event\ClientAuthenticationFailedEvent($this->server->getRequest()));
            throw new Exception\InvalidClientException();
        }

        // Validate any scopes that are in the request
        $scopeParam = $this->server->getRequest()->request->get('scope', '');
        $scopes = $this->validateScopes($scopeParam, $client);

        // Create a new session
        $session = new SessionEntity($this->server);
        $session->setOwner('client', $client->getId());
        $session->associateClient($client);

        // Generate an access token
        $accessToken = new AccessTokenEntity($this->server);
        $accessToken->setId(SecureKey::generate());
        $accessToken->setExpireTime($this->getAccessTokenTTL() + time());

        // Associate scopes with the session and access token
        foreach ($scopes as $scope) {
            $session->associateScope($scope);
            $accessToken->associateScope($scope);
        }

        // Save everything
        $session->save();
        $accessToken->setSession($session);
        $accessToken->save();

        $this->server->getTokenType()->setSession($session);
        $this->server->getTokenType()->setParam('access_token', $accessToken->getId());
        $this->server->getTokenType()->setParam('expires_in', $this->getAccessTokenTTL());

        return $this->server->getTokenType()->generateResponse();
    }

    /**
     * По стандарту OAuth2 scopes должны разделяться пробелом, а не запятой. Косяк.
     * Так что оборачиваем функцию разбора скоупов, заменяя пробелы на запятые.
     *
     * @param string       $scopeParam
     * @param BaseClientEntity $client
     * @param string $redirectUri
     *
     * @return \League\OAuth2\Server\Entity\ScopeEntity[]
     */
    public function validateScopes($scopeParam = '', BaseClientEntity $client, $redirectUri = null) {
        $scopes = str_replace(' ', $this->server->getScopeDelimiter(), $scopeParam);
        return parent::validateScopes($scopes, $client, $redirectUri);
    }

}
