<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use api\components\Tokens\TokenReader;
use Carbon\FactoryImmutable;
use common\components\OAuth2\CryptTrait;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use common\models\OauthSession;
use DateTimeImmutable;
use InvalidArgumentException;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Validator;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant as BaseRefreshTokenGrant;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yii;

/**
 * Access tokens don't expire and can only be revoked, so there are no separate refresh tokens.
 * For backward compatibility, when the offline_access scope is requested, the access_token
 * is also returned as the refresh_token (see OauthProcess::getToken()).
 *
 * This grant is overridden to accept such a token as the refresh_token and issue a new access token from it,
 * as long as the token was issued to the same client with the offline_access scope
 * and the user hasn't revoked the access since then.
 */
final class RefreshTokenGrant extends BaseRefreshTokenGrant {
    use CryptTrait;
    use ValidateRedirectUriTrait;

    /**
     * The original implementation decrypts the refresh token payload, but we use the access token
     * as the refresh token, so it's validated as a JWT instead.
     *
     * @inheritDoc
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, string $clientId): array {
        $refreshToken = $this->getRequestParameter('refresh_token', $request);
        if ($refreshToken === null) {
            throw OAuthServerException::invalidRequest('refresh_token');
        }

        if (mb_strlen($refreshToken) === 40) {
            return $this->validateLegacyRefreshToken($refreshToken, $clientId);
        }

        return $this->validateAccessToken($refreshToken, $clientId);
    }

    /**
     * Currently we're not rotating refresh tokens.
     * So we're overriding this method to always return null, which means,
     * that refresh_token will not be issued.
     */
    protected function issueRefreshToken(AccessTokenEntityInterface $accessToken): ?RefreshTokenEntityInterface {
        return null;
    }

    /**
     * @return array<string, mixed>
     * @throws OAuthServerException
     */
    private function validateLegacyRefreshToken(string $refreshToken, string $clientId): array {
        $result = Yii::$app->redis->get("oauth:refresh:tokens:{$refreshToken}");
        if ($result === null) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
        }

        try {
            [
                'access_token_id' => $accessTokenId,
                'session_id' => $sessionId,
            ] = json_decode((string)$result, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw OAuthServerException::invalidRefreshToken('Cannot decrypt the refresh token', $e);
        }

        /** @var OauthSession|null $relatedSession */
        $relatedSession = OauthSession::findOne(['legacy_id' => $sessionId]);
        if ($relatedSession === null || $relatedSession->revoked_at !== null) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
        }

        if ($relatedSession->client_id !== $clientId) {
            throw OAuthServerException::invalidRefreshToken('Token is not linked to client');
        }

        return [
            'client_id' => $relatedSession->client_id,
            'refresh_token_id' => $refreshToken,
            'access_token_id' => $accessTokenId,
            'scopes' => $relatedSession->getScopes(),
            'user_id' => $relatedSession->account_id,
            'expire_time' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     * @throws OAuthServerException
     */
    private function validateAccessToken(string $jwt, string $clientId): array {
        try {
            $token = Yii::$app->tokens->parse($jwt);
        } catch (InvalidArgumentException $e) {
            throw OAuthServerException::invalidRefreshToken('Cannot decrypt the refresh token', $e);
        }

        if (!Yii::$app->tokens->verify($token)) {
            throw OAuthServerException::invalidRefreshToken('Cannot decrypt the refresh token');
        }

        if (!(new Validator())->validate($token, new LooseValidAt(FactoryImmutable::getDefaultInstance()))) {
            throw OAuthServerException::invalidRefreshToken('Token has expired');
        }

        $reader = new TokenReader($token);
        $tokenClientId = $reader->getClientId();
        $accountId = $reader->getAccountId();
        if ($tokenClientId === null || $accountId === null) {
            throw OAuthServerException::invalidRefreshToken('Token is not a refresh token');
        }

        if ($tokenClientId !== $clientId) {
            throw OAuthServerException::invalidRefreshToken('Token is not linked to client');
        }

        // Since refresh_token is the same value as the access_token, the token can be used as a refresh token
        // only when the offline_access scope was requested
        if (!$reader->hasScope(PublicScopeRepository::OFFLINE_ACCESS)) {
            throw OAuthServerException::invalidRefreshToken('Token is not a refresh token');
        }

        /** @var DateTimeImmutable $iat */
        $iat = $token->claims()->get('iat');
        /** @var OauthSession|null $session */
        $session = OauthSession::findOne(['account_id' => $accountId, 'client_id' => $clientId]);
        // Session is always created when the user grants access, so its absence means that it was revoked
        if ($session === null || $session->isRevokedForTokenIssuedAt($iat)) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
        }

        return [
            'client_id' => $tokenClientId,
            'refresh_token_id' => '', // This value used only to invalidate old token
            'access_token_id' => '', // This value used only to invalidate old token
            'scopes' => $reader->getScopes(),
            'user_id' => $accountId,
            'expire_time' => null,
        ];
    }

}
