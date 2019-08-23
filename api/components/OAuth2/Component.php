<?php
declare(strict_types=1);

namespace api\components\OAuth2;

use api\components\OAuth2\Keys\EmptyKey;
use api\components\OAuth2\Repositories;
use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant;
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
            $clientsRepo = new Repositories\ClientRepository();
            $accessTokensRepo = new Repositories\AccessTokenRepository();
            $scopesRepo = new Repositories\ScopeRepository();
            $authCodesRepo = new Repositories\AuthCodeRepository();
            $refreshTokensRepo = new Repositories\RefreshTokenRepository();

            $accessTokenTTL = new DateInterval('P1D');

            $authServer = new AuthorizationServer(
                $clientsRepo,
                $accessTokensRepo,
                $scopesRepo,
                new EmptyKey(),
                '123' // TODO: extract to the variable
            );
            /** @noinspection PhpUnhandledExceptionInspection */
            $authCodeGrant = new Grant\AuthCodeGrant($authCodesRepo, $refreshTokensRepo, new DateInterval('PT10M'));
            $authCodeGrant->disableRequireCodeChallengeForPublicClients();
            $authServer->enableGrantType($authCodeGrant, $accessTokenTTL);
            $authServer->enableGrantType(new Grant\RefreshTokenGrant($refreshTokensRepo), $accessTokenTTL);
            $authServer->enableGrantType(new Grant\ClientCredentialsGrant(), $accessTokenTTL);

            $this->_authServer = $authServer;
        }

        return $this->_authServer;
    }

}
