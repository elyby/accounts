<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\CryptTrait;
use DateInterval;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant as BaseAuthCodeGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthCodeGrant extends BaseAuthCodeGrant {
    use CryptTrait;
    use CheckOfflineAccessScopeTrait;
    use ValidateRedirectUriTrait;

    /**
     * The library supports the "plain" PKCE method and uses it by default when the method isn't provided.
     * But with the "plain" method the code_challenge is the code_verifier itself and it's passed through
     * the browser, so it doesn't protect from the authorization code interception.
     * That's why only the "S256" method is allowed (RFC 9700 section 2.1.1).
     *
     * @inheritDoc
     */
    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface {
        $authorizationRequest = parent::validateAuthorizationRequest($request);
        $codeChallengeMethod = $authorizationRequest->getCodeChallengeMethod();
        if ($codeChallengeMethod !== null && $codeChallengeMethod !== 'S256') {
            throw OAuthServerException::invalidRequest('code_challenge_method', 'Code challenge method must be `S256`.');
        }

        return $authorizationRequest;
    }

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
