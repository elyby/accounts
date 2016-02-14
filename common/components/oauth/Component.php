<?php
namespace common\components\oauth;

use common\components\oauth\Storage\Redis\AuthCodeStorage;
use common\components\oauth\Storage\Yii2\AccessTokenStorage;
use common\components\oauth\Storage\Yii2\ClientStorage;
use common\components\oauth\Storage\Yii2\ScopeStorage;
use common\components\oauth\Storage\Yii2\SessionStorage;
use League\OAuth2\Server\AuthorizationServer;
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
        'authorization_code' => 'League\OAuth2\Server\Grant\AuthCodeGrant',
        'client_credentials' => 'League\OAuth2\Server\Grant\ClientCredentialsGrant',
        'password'           => 'League\OAuth2\Server\Grant\PasswordGrant',
        'refresh_token'      => 'League\OAuth2\Server\Grant\RefreshTokenGrant'
    ];

    public function getAuthServer() {
        if ($this->_authServer === null) {
            $authServer = new AuthorizationServer();
            $authServer
                ->setAccessTokenStorage(new AccessTokenStorage())
                ->setClientStorage(new ClientStorage())
                ->setScopeStorage(new ScopeStorage())
                ->setSessionStorage(new SessionStorage())
                ->setAuthCodeStorage(new AuthCodeStorage())
                ->setScopeDelimiter(',');

            $this->_authServer = $authServer;

            foreach ($this->grantTypes as $grantType) {
                if (!array_key_exists($grantType, $this->grantMap)) {
                    throw new InvalidConfigException('Invalid grant type');
                }

                $grant = new $this->grantMap[$grantType]();
                $this->_authServer->addGrantType($grant);
            }
        }

        return $this->_authServer;
    }

}
