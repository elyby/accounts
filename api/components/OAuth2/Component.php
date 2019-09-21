<?php
declare(strict_types=1);

namespace api\components\OAuth2;

use api\components\OAuth2\Grants\AuthCodeGrant;
use api\components\OAuth2\Grants\RefreshTokenGrant;
use api\components\OAuth2\Keys\EmptyKey;
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
            $publicScopesRepo = new Repositories\PublicScopeRepository();
            $internalScopesRepo = new Repositories\InternalScopeRepository();
            $authCodesRepo = new Repositories\AuthCodeRepository();
            $refreshTokensRepo = new Repositories\RefreshTokenRepository();

            $accessTokenTTL = new DateInterval('P1D');

            $authServer = new AuthorizationServer(
                $clientsRepo,
                $accessTokensRepo,
                new Repositories\EmptyScopeRepository(),
                new EmptyKey(),
                '123' // TODO: extract to the variable
            );
            /** @noinspection PhpUnhandledExceptionInspection */
            $authCodeGrant = new AuthCodeGrant($authCodesRepo, $refreshTokensRepo, new DateInterval('PT10M'));
            $authCodeGrant->disableRequireCodeChallengeForPublicClients();
            $authServer->enableGrantType($authCodeGrant, $accessTokenTTL);
            $authCodeGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

            $refreshTokenGrant = new RefreshTokenGrant($refreshTokensRepo);
            $authServer->enableGrantType($refreshTokenGrant);
            $refreshTokenGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

            // TODO: make these access tokens live longer
            $clientCredentialsGrant = new Grant\ClientCredentialsGrant();
            $authServer->enableGrantType($clientCredentialsGrant, $accessTokenTTL);
            $clientCredentialsGrant->setScopeRepository($internalScopesRepo); // Change repository after enabling

            $this->_authServer = $authServer;
        }

        return $this->_authServer;
    }

}
