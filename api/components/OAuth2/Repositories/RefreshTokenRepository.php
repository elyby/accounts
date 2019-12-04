<?php
declare(strict_types=1);

namespace api\components\OAuth2\Repositories;

use api\components\OAuth2\Entities\RefreshTokenEntity;
use common\models\OauthRefreshToken;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Webmozart\Assert\Assert;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface {

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void {
        $model = new OauthRefreshToken();
        $model->id = $refreshTokenEntity->getIdentifier();
        $model->account_id = $refreshTokenEntity->getAccessToken()->getUserIdentifier();
        $model->client_id = $refreshTokenEntity->getAccessToken()->getClient()->getIdentifier();

        Assert::true($model->save());
    }

    public function revokeRefreshToken($tokenId): void {
        // Currently we're not rotating refresh tokens so do not revoke
        // token during any OAuth2 grant
    }

    public function isRefreshTokenRevoked($tokenId): bool {
        return OauthRefreshToken::find()->andWhere(['id' => $tokenId])->exists() === false;
    }

}
