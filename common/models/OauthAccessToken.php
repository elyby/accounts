<?php
namespace common\models;

use common\components\Redis\Set;
use yii\db\ActiveRecord;

/**
 * Поля:
 * @property string  $access_token
 * @property string  $session_id
 * @property integer $expire_time
 *
 * Геттеры:
 * @property Set     $scopes
 *
 * Отношения:
 * @property OauthSession $session
 * @deprecated
 */
class OauthAccessToken extends ActiveRecord {

    public static function tableName() {
        return '{{%oauth_access_tokens}}';
    }

    public function getSession() {
        return $this->hasOne(OauthSession::class, ['id' => 'session_id']);
    }

    public function getScopes() {
        return new Set(static::getDb()->getSchema()->getRawTableName(static::tableName()), $this->access_token, 'scopes');
    }

    public function beforeDelete() {
        if (!$result = parent::beforeDelete()) {
            return $result;
        }

        $this->getScopes()->delete();

        return true;
    }

    public function isExpired() : bool {
        return time() > $this->expire_time;
    }

}
