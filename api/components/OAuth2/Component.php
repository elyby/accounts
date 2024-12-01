<?php
declare(strict_types=1);

namespace api\components\OAuth2;

use Carbon\CarbonInterval;
use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use yii\base\Component as BaseComponent;

final class Component extends BaseComponent {

    private ?AuthorizationServer $_authServer = null;

    public function getAuthServer(): AuthorizationServer {
        if ($this->_authServer === null) {
            $this->_authServer = $this->createAuthServer();
        }

        return $this->_authServer;
    }

    private function createAuthServer(): AuthorizationServer {
        $clientsRepo = new Repositories\ClientRepository();
        $accessTokensRepo = new Repositories\AccessTokenRepository();
        $publicScopesRepo = new Repositories\PublicScopeRepository();
        $internalScopesRepo = new Repositories\InternalScopeRepository();
        $authCodesRepo = new Repositories\AuthCodeRepository();
        $refreshTokensRepo = new Repositories\RefreshTokenRepository();

        $accessTokenTTL = CarbonInterval::create(-1); // Set negative value to make tokens non expiring

        $authServer = new AuthorizationServer(
            $clientsRepo,
            $accessTokensRepo,
            new Repositories\EmptyScopeRepository(),
            new Keys\EmptyKey(),
            '', // Omit the key because we use our own encryption mechanism
            new ResponseTypes\BearerTokenResponse(),
        );
        /** @noinspection PhpUnhandledExceptionInspection */
        $authCodeGrant = new Grants\AuthCodeGrant($authCodesRepo, $refreshTokensRepo, new DateInterval('PT10M'));
        $authCodeGrant->disableRequireCodeChallengeForPublicClients();
        $authServer->enableGrantType($authCodeGrant, $accessTokenTTL);
        $authCodeGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

        $refreshTokenGrant = new Grants\RefreshTokenGrant($refreshTokensRepo);
        $authServer->enableGrantType($refreshTokenGrant, $accessTokenTTL);
        $refreshTokenGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

        $clientCredentialsGrant = new Grants\ClientCredentialsGrant();
        $authServer->enableGrantType($clientCredentialsGrant, $accessTokenTTL);
        $clientCredentialsGrant->setScopeRepository($internalScopesRepo); // Change repository after enabling

        return $authServer;
    }

}
