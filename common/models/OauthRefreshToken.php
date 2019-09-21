<?php
declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property string $id
 * @property int    $account_id
 * @property int    $client_id
 * @property int    $issued_at
 *
 * Relations:
 * @property-read OauthSession $session
 * @property-read Account $account
 * @property-read OauthClient $client
 */
class OauthRefreshToken extends ActiveRecord {

    public static function tableName(): string {
        return 'oauth_refresh_tokens';
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'issued_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function getSession(): ActiveQuery {
        return $this->hasOne(OauthSession::class, ['account_id' => 'account_id', 'client_id' => 'client_id']);
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getClient(): ActiveQuery {
        return $this->hasOne(OauthClient::class, ['id' => 'client_id']);
    }

}
