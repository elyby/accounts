<?php
namespace api\components\OAuth2;

use api\components\OAuth2\Storage\AuthCodeStorage;
use api\components\OAuth2\Storage\RefreshTokenStorage;
use api\components\OAuth2\Storage\AccessTokenStorage;
use api\components\OAuth2\Storage\ClientStorage;
use api\components\OAuth2\Storage\ScopeStorage;
use api\components\OAuth2\Storage\SessionStorage;
use api\components\OAuth2\Utils\KeyAlgorithm\UuidAlgorithm;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant;
use League\OAuth2\Server\Util\SecureKey;
use yii\base\InvalidConfigException;

/**
 * @property AuthorizationServer $authServer
 */
class Component extends \yii\base\Component {

    /**
     * @var AuthorizationServer
     */
    private $_authServer;

    /**
     * @var string[]
     */
    public $grantTypes = [];

    /**
     * @var array grant type => class
     */
    public $grantMap = [
        'authorization_code' => Grant\AuthCodeGrant::class,
        'client_credentials' => Grant\ClientCredentialsGrant::class,
        'password'           => Grant\PasswordGrant::class,
        'refresh_token'      => Grant\RefreshTokenGrant::class,
    ];

    public function getAuthServer() {
        if ($this->_authServer === null) {
            $authServer = new AuthorizationServer();
            $authServer->setAccessTokenStorage(new AccessTokenStorage());
            $authServer->setClientStorage(new ClientStorage());
            $authServer->setScopeStorage(new ScopeStorage());
            $authServer->setSessionStorage(new SessionStorage());
            $authServer->setAuthCodeStorage(new AuthCodeStorage());
            $authServer->setRefreshTokenStorage(new RefreshTokenStorage());
            $authServer->setScopeDelimiter(',');
            $authServer->setAccessTokenTTL(86400); // 1d

            $this->_authServer = $authServer;

            foreach ($this->grantTypes as $grantType) {
                if (!isset($this->grantMap[$grantType])) {
                    throw new InvalidConfigException('Invalid grant type');
                }

                /** @var Grant\GrantTypeInterface $grant */
                $grant = new $this->grantMap[$grantType]();
                $this->_authServer->addGrantType($grant);
            }

            SecureKey::setAlgorithm(new UuidAlgorithm());
        }

        return $this->_authServer;
    }

}
