<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Поля:
 * @property string $id
 */
class OauthScope extends ActiveRecord {

    const OFFLINE_ACCESS = 'offline_access';
    const MINECRAFT_SERVER_SESSION = 'minecraft_server_session';

    public static function tableName() {
        return '{{%oauth_scopes}}';
    }

}
