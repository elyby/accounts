<?php
namespace common\components\oauth;

use common\components\oauth\Storage\Redis\AuthCodeStorage;
use common\components\oauth\Storage\Redis\RefreshTokenStorage;
use common\components\oauth\Storage\Yii2\AccessTokenStorage;
use common\components\oauth\Storage\Yii2\ClientStorage;
use common\components\oauth\Storage\Yii2\ScopeStorage;
use common\components\oauth\Storage\Yii2\SessionStorage;
use common\components\oauth\Util\KeyAlgorithm\UuidAlgorithm;
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
            $authServer
                ->setAccessTokenStorage(new AccessTokenStorage())
                ->setClientStorage(new ClientStorage())
                ->setScopeStorage(new ScopeStorage())
                ->setSessionStorage(new SessionStorage())
                ->setAuthCodeStorage(new AuthCodeStorage())
                ->setRefreshTokenStorage(new RefreshTokenStorage())
                ->setScopeDelimiter(',');

            $this->_authServer = $authServer;

            foreach ($this->grantTypes as $grantType) {
                if (!array_key_exists($grantType, $this->grantMap)) {
                    throw new InvalidConfigException('Invalid grant type');
                }

                $grant = new $this->grantMap[$grantType]();
                $this->_authServer->addGrantType($grant);
            }

            SecureKey::setAlgorithm(new UuidAlgorithm());
        }

        return $this->_authServer;
    }

}
