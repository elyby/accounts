<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\CryptTrait;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use common\components\OAuth2\RequestTypes\AuthorizationRequest;
use common\components\OAuth2\ResponseTypes\BearerTokenResponse;
use DateInterval;
use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\AuthCodeGrant as BaseAuthCodeGrant;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use League\OAuth2\Server\ResponseTypes\RedirectResponse;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Webmozart\Assert\Assert;

final class AuthCodeGrant extends BaseAuthCodeGrant {
    use CryptTrait;
    use CheckOfflineAccessScopeTrait;
    use OpenIdAccessTokenTtlTrait;
    use ValidateRedirectUriTrait;

    /**
     * The nonce is stored within the authorization code, so its length is limited to keep the redirect uri short
     */
    private const int MAX_NONCE_LENGTH = 255;

    /**
     * The library keeps the auth code TTL in a private property, but it's required for the completeAuthorizationRequest()
     */
    public function __construct(
        AuthCodeRepositoryInterface $authCodeRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        private readonly DateInterval $authCodeTtl,
    ) {
        parent::__construct($authCodeRepository, $refreshTokenRepository, $authCodeTtl);
    }

    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequestInterface {
        $authorizationRequest = parent::validateAuthorizationRequest($request);
        $codeChallengeMethod = $authorizationRequest->getCodeChallengeMethod();
        // The library supports the "plain" PKCE method and uses it by default when the method isn't provided.
        // But with the "plain" is insecure and we don't want to allow it.
        // That's why only the "S256" method is allowed (RFC 9700 section 2.1.1).
        if ($codeChallengeMethod !== null && $codeChallengeMethod !== 'S256') {
            throw OAuthServerException::invalidRequest('code_challenge_method', 'Code challenge method must be `S256`.');
        }

        $this->assertRequestObjectIsNotPassed($request, $authorizationRequest);

        // The nonce is the OpenID Connect parameter, so it's ignored for the plain OAuth 2.0 requests
        if ($authorizationRequest instanceof AuthorizationRequest && self::hasOpenIdScope($authorizationRequest->getScopes())) {
            // The raw value is used, since the id_token must contain exactly the same value (OpenID Connect Core 3.1.3.7)
            $nonce = $request->getQueryParams()['nonce'] ?? null;
            if ($nonce !== null && (!is_string($nonce) || strlen($nonce) > self::MAX_NONCE_LENGTH || preg_match('//u', $nonce) !== 1)) {
                throw OAuthServerException::invalidRequest('nonce', sprintf('The nonce must be a valid UTF-8 string not longer than %d bytes.', self::MAX_NONCE_LENGTH));
            }

            // Don't use empty(), it will skip '0' nonce
            if ($nonce !== null && $nonce !== '') {
                $authorizationRequest->setNonce($nonce);
            }
        }

        return $authorizationRequest;
    }

    /**
     * The original method is copied from the league/oauth2-server to add the nonce into the authorization code's payload,
     * since the library doesn't allow to extend it. The method must be kept in sync with the library's one.
     *
     * @inheritDoc
     */
    public function completeAuthorizationRequest(AuthorizationRequestInterface $authorizationRequest): ResponseTypeInterface {
        Assert::isInstanceOf($authorizationRequest->getUser(), UserEntityInterface::class);

        $finalRedirectUri = $authorizationRequest->getRedirectUri() ?? $this->getClientRedirectUri($authorizationRequest->getClient());

        if ($authorizationRequest->isAuthorizationApproved() !== true) {
            throw OAuthServerException::accessDenied(
                'The user denied the request',
                $this->makeRedirectUri($finalRedirectUri, [
                    'state' => $authorizationRequest->getState(),
                ]),
            );
        }

        $authCode = $this->issueAuthCode(
            authCodeTTL: $this->authCodeTtl,
            client: $authorizationRequest->getClient(),
            userIdentifier: $authorizationRequest->getUser()->getIdentifier(),
            redirectUri: $authorizationRequest->getRedirectUri(),
            scopes: $authorizationRequest->getScopes(),
        );

        $payload = [
            'client_id' => $authCode->getClient()->getIdentifier(),
            'redirect_uri' => $authCode->getRedirectUri(),
            'auth_code_id' => $authCode->getIdentifier(),
            'scopes' => $authCode->getScopes(),
            'user_id' => $authCode->getUserIdentifier(),
            'expire_time' => (new DateTimeImmutable())->add($this->authCodeTtl)->getTimestamp(),
            'code_challenge' => $authorizationRequest->getCodeChallenge(),
            'code_challenge_method' => $authorizationRequest->getCodeChallengeMethod(),
        ];
        if ($authorizationRequest instanceof AuthorizationRequest && $authorizationRequest->getNonce() !== null) {
            $payload['nonce'] = $authorizationRequest->getNonce();
        }

        $response = new RedirectResponse();
        $response->setRedirectUri($this->makeRedirectUri($finalRedirectUri, [
            'code' => $this->encrypt(json_encode($payload, JSON_THROW_ON_ERROR)),
            'state' => $authorizationRequest->getState(),
        ]));

        return $response;
    }

    /**
     * Passes the nonce from the authorization code to the id_token (OpenID Connect Core 1.0 section 3.1.3.6)
     *
     * @inheritDoc
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL,
    ): ResponseTypeInterface {
        $responseType = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
        if ($responseType instanceof BearerTokenResponse) {
            // At this point the code is already validated by the parent method
            $payload = json_decode($this->decrypt((string)$this->getRequestParameter('code', $request)), true);
            $nonce = is_array($payload) ? ($payload['nonce'] ?? null) : null;
            $responseType->setNonce(is_string($nonce) && $nonce !== '' ? $nonce : null);
        }

        return $responseType;
    }

    protected function createAuthorizationRequest(): AuthorizationRequestInterface {
        return new AuthorizationRequest();
    }

    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        ?string $userIdentifier,
        array $scopes = [],
    ): AccessTokenEntityInterface {
        $this->checkOfflineAccessScope($scopes);

        return parent::issueAccessToken($this->resolveAccessTokenTtl($accessTokenTTL, $scopes), $client, $userIdentifier, $scopes);
    }

    /**
     * The request objects (OpenID Connect Core 1.0 section 6, RFC 9101) aren't supported. The client may pass
     * some parameters only within the request object, so the request must be rejected instead of ignoring them.
     * The redirect uri is already validated at this point, so the error is returned to the client
     * (OpenID Connect Core 1.0 section 3.1.2.6).
     *
     * @throws OAuthServerException
     */
    private function assertRequestObjectIsNotPassed(
        ServerRequestInterface $request,
        AuthorizationRequestInterface $authorizationRequest,
    ): void {
        $queryParams = $request->getQueryParams();
        foreach (['request', 'request_uri'] as $param) {
            if (!array_key_exists($param, $queryParams)) {
                continue;
            }

            $state = $authorizationRequest->getState();
            throw new OAuthServerException(
                message: sprintf('The `%s` parameter is not supported.', $param),
                code: 3, // Just because the same code used within "invalidRequest" static method
                errorType: "{$param}_not_supported",
                httpStatusCode: 400,
                hint: sprintf('Pass the authorization request parameters directly instead of the `%s` parameter', $param),
                redirectUri: $this->makeRedirectUri(
                    $authorizationRequest->getRedirectUri() ?? $this->getClientRedirectUri($authorizationRequest->getClient()),
                    $state !== null ? ['state' => $state] : [],
                ),
            );
        }
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     */
    private static function hasOpenIdScope(array $scopes): bool {
        foreach ($scopes as $scope) {
            if ($scope->getIdentifier() === PublicScopeRepository::OPENID) {
                return true;
            }
        }

        return false;
    }

}
