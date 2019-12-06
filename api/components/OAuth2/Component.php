<?php
declare(strict_types=1);

namespace api\components\OAuth2;

use api\components\OAuth2\Keys\EmptyKey;
use Carbon\CarbonInterval;
use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use yii\base\Component as BaseComponent;

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

            $accessTokenTTL = CarbonInterval::day();

            $authServer = new AuthorizationServer(
                $clientsRepo,
                $accessTokensRepo,
                new Repositories\EmptyScopeRepository(),
                new EmptyKey(),
                '', // omit key because we use our own encryption mechanism
                new ResponseTypes\BearerTokenResponse()
            );
            /** @noinspection PhpUnhandledExceptionInspection */
            $authCodeGrant = new Grants\AuthCodeGrant($authCodesRepo, $refreshTokensRepo, new DateInterval('PT10M'));
            $authCodeGrant->disableRequireCodeChallengeForPublicClients();
            $authServer->enableGrantType($authCodeGrant, $accessTokenTTL);
            $authCodeGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

            $refreshTokenGrant = new Grants\RefreshTokenGrant($refreshTokensRepo);
            $authServer->enableGrantType($refreshTokenGrant);
            $refreshTokenGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

            $clientCredentialsGrant = new Grants\ClientCredentialsGrant();
            $authServer->enableGrantType($clientCredentialsGrant, CarbonInterval::create(-1)); // set negative value to make it non expiring
            $clientCredentialsGrant->setScopeRepository($internalScopesRepo); // Change repository after enabling

            $this->_authServer = $authServer;
        }

        return $this->_authServer;
    }

}
