<?php
declare(strict_types=1);

namespace api\components\OAuth2\Grants;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Grant\RefreshTokenGrant as BaseRefreshTokenGrant;

class RefreshTokenGrant extends BaseRefreshTokenGrant {

    /**
     * Currently we're not rotating refresh tokens.
     * So we overriding this method to always return null, which means,
     * that refresh_token will not be issued.
     *
     * @param AccessTokenEntityInterface $accessToken
     *
     * @return RefreshTokenEntityInterface|null
     */
    protected function issueRefreshToken(AccessTokenEntityInterface $accessToken): ?RefreshTokenEntityInterface {
        return null;
    }

}
