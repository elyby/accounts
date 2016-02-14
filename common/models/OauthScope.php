<?php
namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Поля:
 * @property string $id
 */
class OauthScope extends ActiveRecord {

    public static function tableName() {
        return '{{%oauth_scopes}}';
    }

}
