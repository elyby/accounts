<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * Поля модели:
 * @property integer  $id
 * @property integer  $account_id
 * @property string   $key
 * @property integer  $type
 * @property integer  $created_at
 *
 * Отношения:
 * @property Account $account
 *
 * Поведения:
 * @mixin TimestampBehavior
 */
class EmailActivation extends \yii\db\ActiveRecord {

    const TYPE_REGISTRATION_EMAIL_CONFIRMATION = 0;

    public static function tableName() {
        return '{{%email_activations}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules() {
        return [];
    }

    public function getAccount() {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

}
