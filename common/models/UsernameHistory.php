<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Поля модели:
 * @property integer $id
 * @property string  $username
 * @property integer $account_id
 * @property integer $applied_in
 *
 * Отношения:
 * @property Account $account
 *
 * Поведения:
 * @mixin TimestampBehavior
 */
class UsernameHistory extends ActiveRecord {

    public static function tableName() {
        return '{{%usernames_history}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'applied_in',
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
