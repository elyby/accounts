<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\CryptTrait;
use DateInterval;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Grant\AuthCodeGrant as BaseAuthCodeGrant;

final class AuthCodeGrant extends BaseAuthCodeGrant {
    use CryptTrait;
    use CheckOfflineAccessScopeTrait;
    use ValidateRedirectUriTrait;

    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        ?string $userIdentifier,
        array $scopes = [],
    ): AccessTokenEntityInterface {
        $this->checkOfflineAccessScope($scopes);
        return parent::issueAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);
    }

}
