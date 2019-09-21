<?php
declare(strict_types=1);

namespace api\components\OAuth2\Grants;

use api\components\OAuth2\Repositories\PublicScopeRepository;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Grant\AuthCodeGrant as BaseAuthCodeGrant;

class AuthCodeGrant extends BaseAuthCodeGrant {

    protected function issueRefreshToken(AccessTokenEntityInterface $accessToken): ?RefreshTokenEntityInterface {
        foreach ($accessToken->getScopes() as $scope) {
            if ($scope->getIdentifier() === PublicScopeRepository::OFFLINE_ACCESS) {
                return parent::issueRefreshToken($accessToken);
            }
        }

        return null;
    }

}
