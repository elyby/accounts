<?php
namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Поля модели:
 * @property string  $username
 * @property string  $uuid
 * @property integer $last_pulled_at
 *
 * Поведения:
 * @mixin TimestampBehavior
 */
class MojangUsername extends ActiveRecord {

    public static function tableName() {
        return '{{%mojang_usernames}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => false,
                'updatedAtAttribute' => 'last_pulled_at',
            ],
        ];
    }

}
