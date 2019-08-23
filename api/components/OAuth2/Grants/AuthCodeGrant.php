<?php
namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Entities\AccessTokenEntity;
use api\components\OAuth2\Entities\AuthCodeEntity;
use api\components\OAuth2\Entities\ClientEntity;
use api\components\OAuth2\Entities\RefreshTokenEntity;
use api\components\OAuth2\Entities\SessionEntity;
use api\components\OAuth2\Repositories\ScopeStorage;
use api\components\OAuth2\Utils\Scopes;
use League\OAuth2\Server\Entity\AuthCodeEntity as BaseAuthCodeEntity;
use League\OAuth2\Server\Entity\ClientEntity as BaseClientEntity;
use League\OAuth2\Server\Event\ClientAuthenticationFailedEvent;
use League\OAuth2\Server\Exception;
use League\OAuth2\Server\Grant\AbstractGrant;
use League\OAuth2\Server\Util\SecureKey;

class AuthCodeGrant extends AbstractGrant {

    protected $identifier = 'authorization_code';

    protected $responseType = 'code';

    protected $authTokenTTL = 600;

    protected $requireClientSecret = true;

    public function setAuthTokenTTL(int $authTokenTTL): void {
        $this->authTokenTTL = $authTokenTTL;
    }

    public function setRequireClientSecret(bool $required): void {
        $this->requireClientSecret = $required;
    }

    public function shouldRequireClientSecret(): bool {
        return $this->requireClientSecret;
    }

    /**
     * Check authorize parameters
     *
     * @return AuthorizeParams Authorize request parameters
     * @throws Exception\OAuthException
     *
     * @throws
     */
    public function checkAuthorizeParams(): AuthorizeParams {
        // Get required params
        $clientId = $this->server->getRequest()->query->get('client_id');
        if ($clientId === null) {
            throw new Exception\InvalidRequestException('client_id');
        }

        $redirectUri = $this->server->getRequest()->query->get('redirect_uri');
        if ($redirectUri === null) {
            throw new Exception\InvalidRequestException('redirect_uri');
        }

        // Validate client ID and redirect URI
        $client = $this->server->getClientStorage()->get($clientId, null, $redirectUri, $this->getIdentifier());
        if (!$client instanceof ClientEntity) {
            $this->server->getEventEmitter()->emit(new ClientAuthenticationFailedEvent($this->server->getRequest()));
            throw new Exception\InvalidClientException();
        }

        $state = $this->server->getRequest()->query->get('state');
        if ($state === null && $this->server->stateParamRequired()) {
            throw new Exception\InvalidRequestException('state', $redirectUri);
        }

        $responseType = $this->server->getRequest()->query->get('response_type');
        if ($responseType === null) {
            throw new Exception\InvalidRequestException('response_type', $redirectUri);
        }

        // Ensure response type is one that is recognised
        if (!in_array($responseType, $this->server->getResponseTypes(), true)) {
            throw new Exception\UnsupportedResponseTypeException($responseType, $redirectUri);
        }

        // Validate any scopes that are in the request
        $scopeParam = $this->server->getRequest()->query->get('scope', '');
        $scopes = $this->validateScopes($scopeParam, $client, $redirectUri);

        return new AuthorizeParams($client, $redirectUri, $state, $responseType, $scopes);
    }

    /**
     * Parse a new authorize request
     *
     * @param string $type       The session owner's type
     * @param string $typeId     The session owner's ID
     * @param AuthorizeParams $authParams The authorize request $_GET parameters
     *
     * @return string An authorisation code
     */
    public function newAuthorizeRequest(string $type, string $typeId, AuthorizeParams $authParams): string {
        // Create a new session
        $session = new SessionEntity($this->server);
        $session->setOwner($type, $typeId);
        $session->associateClient($authParams->getClient());

        // Create a new auth code
        $authCode = new AuthCodeEntity($this->server);
        $authCode->setId(SecureKey::generate());
        $authCode->setRedirectUri($authParams->getRedirectUri());
        $authCode->setExpireTime(time() + $this->authTokenTTL);

        foreach ($authParams->getScopes() as $scope) {
            $authCode->associateScope($scope);
            $session->associateScope($scope);
        }

        $session->save();
        $authCode->setSession($session);
        $authCode->save();

        return $authCode->generateRedirectUri($authParams->getState());
    }

    /**
     * Complete the auth code grant
     *
     * @return array
     *
     * @throws Exception\OAuthException
     */
    public function completeFlow(): array {
        // Get the required params
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

        $redirectUri = $this->server->getRequest()->request->get('redirect_uri');
        if ($redirectUri === null) {
            throw new Exception\InvalidRequestException('redirect_uri');
        }

        // Validate client ID and client secret
        $client = $this->server->getClientStorage()->get($clientId, $clientSecret, $redirectUri, $this->getIdentifier());
        if (!$client instanceof BaseClientEntity) {
            $this->server->getEventEmitter()->emit(new ClientAuthenticationFailedEvent($this->server->getRequest()));
            throw new Exception\InvalidClientException();
        }

        // Validate the auth code
        $authCode = $this->server->getRequest()->request->get('code');
        if ($authCode === null) {
            throw new Exception\InvalidRequestException('code');
        }

        $code = $this->server->getAuthCodeStorage()->get($authCode);
        if (($code instanceof BaseAuthCodeEntity) === false) {
            throw new Exception\InvalidRequestException('code');
        }

        // Ensure the auth code hasn't expired
        if ($code->isExpired()) {
            throw new Exception\InvalidRequestException('code');
        }

        // Check redirect URI presented matches redirect URI originally used in authorize request
        if ($code->getRedirectUri() !== $redirectUri) {
            throw new Exception\InvalidRequestException('redirect_uri');
        }

        $session = $code->getSession();
        $session->associateClient($client);

        $authCodeScopes = $code->getScopes();

        // Generate the access token
        $accessToken = new AccessTokenEntity($this->server);
        $accessToken->setId(SecureKey::generate());
        $accessToken->setExpireTime($this->getAccessTokenTTL() + time());

        foreach ($authCodeScopes as $authCodeScope) {
            $session->associateScope($authCodeScope);
        }

        foreach ($session->getScopes() as $scope) {
            $accessToken->associateScope($scope);
        }

        $this->server->getTokenType()->setSession($session);
        $this->server->getTokenType()->setParam('access_token', $accessToken->getId());
        $this->server->getTokenType()->setParam('expires_in', $this->getAccessTokenTTL());

        // Set refresh_token param only in case when offline_access requested
        if (isset($accessToken->getScopes()[ScopeStorage::OFFLINE_ACCESS])) {
            /** @var RefreshTokenGrant $refreshTokenGrant */
            $refreshTokenGrant = $this->server->getGrantType('refresh_token');
            $refreshToken = new RefreshTokenEntity($this->server);
            $refreshToken->setId(SecureKey::generate());
            $refreshToken->setExpireTime($refreshTokenGrant->getRefreshTokenTTL() + time());
            $this->server->getTokenType()->setParam('refresh_token', $refreshToken->getId());
        }

        // Expire the auth code
        $code->expire();

        // Save all the things
        $accessToken->setSession($session);
        $accessToken->save();

        if (isset($refreshToken)) {
            $refreshToken->setAccessToken($accessToken);
            $refreshToken->save();
        }

        return $this->server->getTokenType()->generateResponse();
    }

    /**
     * In the earlier versions of Accounts Ely.by backend we had a comma-separated scopes
     * list, while by OAuth2 standard it they should be separated by a space. Shit happens :)
     * So override scopes validation function to reformat passed value.
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

}
