<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\Repositories\PublicScopeRepository;
use DateInterval;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

trait OpenIdAccessTokenTtlTrait {

    /**
     * The access tokens don't expire by default. But the OpenID Connect clients usually need the access token
     * only to request the userinfo endpoint right after the authentication, so there is no reason to give them
     * the unlimited access. When the openid scope is requested without the offline_access, the token lives for an hour.
     *
     * The offline_access tokens are kept non-expiring, since the same value is used as the refresh token
     * (see RefreshTokenGrant).
     *
     * @param ScopeEntityInterface[] $scopes
     */
    protected function resolveAccessTokenTtl(DateInterval $defaultTtl, array $scopes): DateInterval {
        $identifiers = array_map(fn(ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes);
        if (in_array(PublicScopeRepository::OPENID, $identifiers, true)
         && !in_array(PublicScopeRepository::OFFLINE_ACCESS, $identifiers, true)
        ) {
            return new DateInterval('PT1H');
        }

        return $defaultTtl;
    }

}
