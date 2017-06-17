<?php
namespace api\components\OAuth2;

use api\components\OAuth2\Storage;
use api\components\OAuth2\Utils\KeyAlgorithm\UuidAlgorithm;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Util\SecureKey;
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
            $authServer->setScopeDelimiter(',');
            $authServer->setAccessTokenTTL(86400); // 1d

            $authServer->addGrantType(new Grants\AuthCodeGrant());
            $authServer->addGrantType(new Grants\RefreshTokenGrant());
            $authServer->addGrantType(new Grants\ClientCredentialsGrant());

            $this->_authServer = $authServer;

            SecureKey::setAlgorithm(new UuidAlgorithm());
        }

        return $this->_authServer;
    }

}
