<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Поля модели:
 * @property string         $id
 * @property string         $secret
 * @property string         $name
 * @property string         $description
 * @property string         $redirect_uri
 * @property integer        $account_id
 * @property bool           $is_trusted
 * @property integer        $created_at
 *
 * Отношения:
 * @property Account        $account
 * @property OauthSession[] $sessions
 */
class OauthClient extends ActiveRecord {

    public static function tableName() {
        return '{{%oauth_clients}}';
    }

    public function rules() {
        return [
            [['id'], 'required', 'when' => function(self $model) {
                return $model->isNewRecord;
            }],
            [['id'], 'unique', 'when' => function(self $model) {
                return $model->isNewRecord;
            }],
            [['name', 'description'], 'required'],
            [['name', 'description'], 'string', 'max' => 255],
        ];
    }

    public function getAccount() {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getSessions() {
        return $this->hasMany(OauthSession::class, ['client_id' => 'id']);
    }

}
