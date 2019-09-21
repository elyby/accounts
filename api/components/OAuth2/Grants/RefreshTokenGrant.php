<?php
declare(strict_types=1);

namespace api\components\OAuth2\Grants;

use common\models\OauthSession;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant as BaseRefreshTokenGrant;
use Psr\Http\Message\ServerRequestInterface;
use Yii;

class RefreshTokenGrant extends BaseRefreshTokenGrant {

    /**
     * Previously, refresh tokens was stored in Redis.
     * If received refresh token is matches the legacy token template,
     * restore the information from the legacy storage.
     *
     * @param ServerRequestInterface $request
     * @param string $clientId
     *
     * @return array
     * @throws OAuthServerException
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, $clientId): array {
        $refreshToken = $this->getRequestParameter('refresh_token', $request);
        if ($refreshToken !== null && mb_strlen($refreshToken) === 40) {
            return $this->validateLegacyRefreshToken($refreshToken);
        }

        return parent::validateOldRefreshToken($request, $clientId);
    }

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

    private function validateLegacyRefreshToken(string $refreshToken): array {
        $result = Yii::$app->redis->get("oauth:refresh:tokens:{$refreshToken}");
        if ($result === null) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
        }

        try {
            [
                'access_token_id' => $accessTokenId,
                'session_id' => $sessionId,
            ] = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
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

}
