<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use api\components\Tokens\TokenReader;
use Carbon\FactoryImmutable;
use common\components\OAuth2\CryptTrait;
use common\models\OauthSession;
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

final class RefreshTokenGrant extends BaseRefreshTokenGrant {
    use CryptTrait;
    use ValidateRedirectUriTrait;

    /**
     * Previously, refresh tokens were stored in Redis.
     * If received refresh token is matches the legacy token template,
     * restore the information from the legacy storage.
     *
     * @inheritDoc
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, string $clientId): array {
        $refreshToken = $this->getRequestParameter('refresh_token', $request);
        if ($refreshToken !== null && mb_strlen($refreshToken) === 40) {
            return $this->validateLegacyRefreshToken($refreshToken);
        }

        return $this->validateAccessToken($refreshToken);
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
    private function validateLegacyRefreshToken(string $refreshToken): array {
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
        if ($relatedSession === null) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
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
    private function validateAccessToken(string $jwt): array {
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

        return [
            'client_id' => $reader->getClientId(),
            'refresh_token_id' => '', // This value used only to invalidate old token
            'access_token_id' => '', // This value used only to invalidate old token
            'scopes' => $reader->getScopes(),
            'user_id' => $reader->getAccountId(),
            'expire_time' => null,
        ];
    }

}
