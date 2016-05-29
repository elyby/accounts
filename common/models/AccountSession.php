<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Поля модели:
 * @property integer $id
 * @property integer $account_id
 * @property string  $refresh_token
 * @property integer $last_used_ip
 * @property integer $created_at
 * @property integer $last_refreshed
 *
 * Отношения:
 * @property Account $account
 *
 * Поведения:
 * @mixin TimestampBehavior
 */
class AccountSession extends ActiveRecord {

    public static function tableName() {
        return '{{%accounts_sessions}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => 'last_refreshed_at',
            ]
        ];
    }

    public function getAccount() {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function generateRefreshToken() {
        $this->refresh_token = Yii::$app->security->generateRandomString(96);
    }

    public function setIp($ip) {
        $this->last_used_ip = ip2long($ip);
    }

    public function getReadableIp() {
        return long2ip($this->last_used_ip);
    }

}
