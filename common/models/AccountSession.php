<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property integer $id
 * @property integer $account_id
 * @property string  $refresh_token
 * @property integer $last_used_ip
 * @property integer $created_at
 * @property integer $last_refreshed_at
 *
 * Relations:
 * @property Account $account
 *
 * Behaviors:
 * @mixin TimestampBehavior
 */
class AccountSession extends ActiveRecord {

    public static function tableName(): string {
        return '{{%accounts_sessions}}';
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => 'last_refreshed_at',
            ],
        ];
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function generateRefreshToken(): void {
        $this->refresh_token = Yii::$app->security->generateRandomString(96);
    }

    public function setIp($ip): void {
        $this->last_used_ip = ip2long($ip);
    }

    public function getReadableIp(): string {
        return long2ip($this->last_used_ip);
    }

}
