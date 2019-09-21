<?php
declare(strict_types=1);

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property int     $account_id
 * @property string  $client_id
 * @property int     $legacy_id
 * @property array   $scopes
 * @property integer $created_at
 *
 * Relations:
 * @property-read OauthClient $client
 * @property-read Account $account
 * @property-read OauthRefreshToken[] $refreshTokens
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
        return $this->hasOne(Account::class, ['id' => 'owner_id']);
    }

    public function getRefreshTokens(): ActiveQuery {
        return $this->hasMany(OauthRefreshToken::class, ['account_id' => 'account_id', 'client_id' => 'client_id']);
    }

    public function getScopes(): array {
        if (empty($this->scopes) && $this->legacy_id !== null) {
            return Yii::$app->redis->smembers($this->getLegacyRedisScopesKey());
        }

        return (array)$this->scopes;
    }

    public function beforeDelete(): bool {
        if (!parent::beforeDelete()) {
            return false;
        }

        if ($this->legacy_id !== null) {
            Yii::$app->redis->del($this->getLegacyRedisScopesKey());
        }

        return true;
    }

    private function getLegacyRedisScopesKey(): string {
        return "oauth:sessions:{$this->legacy_id}:scopes";
    }

}
