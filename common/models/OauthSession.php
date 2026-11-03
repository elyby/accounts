<?php
declare(strict_types=1);

namespace common\models;

use common\components\OAuth2\ScopesMapper;
use DateTimeInterface;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property int $account_id
 * @property string $client_id
 * @property int|null $legacy_id
 * @property array $scopes
 * @property int $created_at
 * @property int|null $revoked_at
 * @property int $last_used_at
 *
 * Relations:
 * @property-read OauthClient|null $client
 * @property-read Account $account
 *
 * Mixins:
 * @mixin TimestampBehavior
 */
class OauthSession extends ActiveRecord {

    public static function tableName(): string {
        return 'oauth_sessions';
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    /**
     * There are 2 ways to revoke tokens:
     * 1. By setting revoked_at time.
     * 2. By completely removing the session record.
     *
     * For the second case we need to ensure, that the validated token is not before the latest existing session.
     */
    public function isRevokedForTokenIssuedAt(DateTimeInterface $issuedAt): bool {
        $issuedAt = $issuedAt->getTimestamp();
        if ($issuedAt < $this->created_at) {
            return true;
        }

        return $this->revoked_at !== null && $this->revoked_at > $issuedAt;
    }

    public function getClient(): ActiveQuery {
        return $this->hasOne(OauthClient::class, ['id' => 'client_id']);
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    /**
     * Previously the RBAC permissions names were stored instead of the public scopes names.
     * Since there are too many rows to migrate them, the scopes are normalized on read
     * and will be stored in the normalized form on the next session update.
     *
     * @return list<non-empty-string>
     */
    public function getScopes(): array {
        return ScopesMapper::normalizeScopes((array)$this->scopes);
    }

}
