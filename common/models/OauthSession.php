<?php
declare(strict_types=1);

namespace common\models;

use Yii;
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
 *
 * Relations:
 * @property-read OauthClient $client
 * @property-read Account $account
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

    public function getClient(): ActiveQuery {
        return $this->hasOne(OauthClient::class, ['id' => 'client_id']);
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getScopes(): array {
        if (empty($this->scopes) && $this->legacy_id !== null) {
            return Yii::$app->redis->smembers($this->getLegacyRedisScopesKey());
        }

        return (array)$this->scopes;
    }

    /**
     * In the early period of the project existence, the refresh tokens related to the current session
     * were stored in Redis. This method allows to get a list of these tokens.
     *
     * @return array of refresh tokens (ids)
     */
    public function getLegacyRefreshTokens(): array {
        // TODO: it seems that this method isn't used anywhere
        if ($this->legacy_id === null) {
            return [];
        }

        return Yii::$app->redis->smembers($this->getLegacyRedisRefreshTokensKey());
    }

    public function beforeDelete(): bool {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->legacy_id !== null) {
            Yii::$app->redis->del($this->getLegacyRedisScopesKey());
            Yii::$app->redis->del($this->getLegacyRedisRefreshTokensKey());
        }

        return true;
    }

    private function getLegacyRedisScopesKey(): string {
        return "oauth:sessions:{$this->legacy_id}:scopes";
    }

    private function getLegacyRedisRefreshTokensKey(): string {
        return "oauth:sessions:{$this->legacy_id}:refresh:tokens";
    }

}
