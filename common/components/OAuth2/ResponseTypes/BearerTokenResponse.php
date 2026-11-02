<?php
declare(strict_types=1);

namespace common\components\OAuth2\ResponseTypes;

use common\components\OAuth2\CryptTrait;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;

final class BearerTokenResponse extends BaseBearerTokenResponse {
    use CryptTrait;

    /**
     * The granted scopes may differ from the requested ones, so they're returned in the response (RFC 6749 section 5.1).
     *
     * @return array<string, string>
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array {
        $scopes = array_map(fn(ScopeEntityInterface $scope): string => $scope->getIdentifier(), $accessToken->getScopes());
        if (empty($scopes)) {
            return [];
        }

        return [
            'scope' => implode(' ', $scopes),
        ];
    }

}
