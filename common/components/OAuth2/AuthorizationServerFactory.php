<?php
declare(strict_types=1);

namespace common\components\OAuth2;

use Carbon\CarbonInterval;
use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use Yii;

final class AuthorizationServerFactory {

    public static function build(): AuthorizationServer {
        $clientsRepo = new Repositories\ClientRepository();
        $accessTokensRepo = new Repositories\AccessTokenRepository();
        $publicScopesRepo = new Repositories\PublicScopeRepository();
        $internalScopesRepo = new Repositories\InternalScopeRepository();
        $authCodesRepo = new Repositories\AuthCodeRepository();
        $refreshTokensRepo = new Repositories\RefreshTokenRepository();
        $deviceCodesRepo = new Repositories\DeviceCodeRepository();

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
        $authServer->enableGrantType($authCodeGrant, $accessTokenTTL);
        $authCodeGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

        $refreshTokenGrant = new Grants\RefreshTokenGrant($refreshTokensRepo);
        $authServer->enableGrantType($refreshTokenGrant, $accessTokenTTL);
        $refreshTokenGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

        $clientCredentialsGrant = new Grants\ClientCredentialsGrant();
        $authServer->enableGrantType($clientCredentialsGrant, $accessTokenTTL);
        $clientCredentialsGrant->setScopeRepository($internalScopesRepo); // Change repository after enabling

        $verificationUri = Yii::$app->request->getHostInfo() . '/code';
        $deviceCodeGrant = new Grants\DeviceCodeGrant($deviceCodesRepo, $refreshTokensRepo, new DateInterval('PT10M'), $verificationUri);
        $deviceCodeGrant->setIntervalVisibility(true);
        $authServer->enableGrantType($deviceCodeGrant, $accessTokenTTL);
        $deviceCodeGrant->setScopeRepository($publicScopesRepo); // Change repository after enabling

        return $authServer;
    }

}
