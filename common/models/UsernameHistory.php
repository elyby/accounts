<?php
namespace common\models;

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

    /**
     * @param int $afterTime
     * @return UsernameHistory|null
     */
    public function findNext(int $afterTime = null): ?UsernameHistory {
        return self::find()
            ->andWhere(['account_id' => $this->account_id])
            ->andWhere(['>', 'applied_in', $afterTime ?: $this->applied_in])
            ->orderBy(['applied_in' => SORT_ASC])
            ->one();
    }

}
