<?php
namespace common\models;

use common\components\redis\Set;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "oauth_access_tokens".
 *
 * @property string  $access_token
 * @property string  $session_id
 * @property integer $expire_time
 *
 * @property Set     $scopes
 *
 * @property OauthSession $session
 */
class OauthAccessToken extends ActiveRecord {

    public static function tableName() {
        return '{{%oauth_access_tokens}}';
    }

    public function getSession() {
        return $this->hasOne(OauthSession::class, ['id' => 'session_id']);
    }

    public function getScopes() {
        return new Set($this->getDb()->getSchema()->getRawTableName($this->tableName()), $this->access_token, 'scopes');
    }

    public function beforeDelete() {
        if (!$result = parent::beforeDelete()) {
            return $result;
        }

        $this->getScopes()->delete();

        return true;
    }

    public function isExpired() {
        return time() > $this->expire_time;
    }

}
