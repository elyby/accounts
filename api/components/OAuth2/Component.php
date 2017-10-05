<?php
namespace api\components\OAuth2;

use api\components\OAuth2\Storage;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use League\OAuth2\Server\Storage\RefreshTokenInterface;
use League\OAuth2\Server\Storage\SessionInterface;
use yii\base\Component as BaseComponent;

/**
 * @property AuthorizationServer $authServer
 */
class Component extends BaseComponent {

    /**
     * @var AuthorizationServer
     */
    private $_authServer;

    public function getAuthServer(): AuthorizationServer {
        if ($this->_authServer === null) {
            $authServer = new AuthorizationServer();
            $authServer->setAccessTokenStorage(new Storage\AccessTokenStorage());
            $authServer->setClientStorage(new Storage\ClientStorage());
            $authServer->setScopeStorage(new Storage\ScopeStorage());
            $authServer->setSessionStorage(new Storage\SessionStorage());
            $authServer->setAuthCodeStorage(new Storage\AuthCodeStorage());
            $authServer->setRefreshTokenStorage(new Storage\RefreshTokenStorage());
            $authServer->setAccessTokenTTL(86400); // 1d

            $authServer->addGrantType(new Grants\AuthCodeGrant());
            $authServer->addGrantType(new Grants\RefreshTokenGrant());
            $authServer->addGrantType(new Grants\ClientCredentialsGrant());

            $this->_authServer = $authServer;
        }

        return $this->_authServer;
    }

    public function getAccessTokenStorage(): AccessTokenInterface {
        return $this->getAuthServer()->getAccessTokenStorage();
    }

    public function getRefreshTokenStorage(): RefreshTokenInterface {
        return $this->getAuthServer()->getRefreshTokenStorage();
    }

    public function getSessionStorage(): SessionInterface {
        return $this->getAuthServer()->getSessionStorage();
    }

}
