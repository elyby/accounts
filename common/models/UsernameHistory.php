<?php
declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property int      $id
 * @property string   $username
 * @property int|null $account_id
 * @property int      $applied_in
 *
 * Relations:
 * @property-read Account $account
 *
 * Behaviors:
 * @mixin TimestampBehavior
 */
class UsernameHistory extends ActiveRecord {

    public static function tableName(): string {
        return 'usernames_history';
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'applied_in',
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    /**
     * Find the username after the current of the account.
     *
     * @param int $afterTime
     * @return UsernameHistory|null
     */
    public function findNextOwnerUsername(int $afterTime = null): ?self {
        return self::find()
            ->andWhere(['account_id' => $this->account_id])
            ->andWhere(['>', 'applied_in', $afterTime ?: $this->applied_in])
            ->orderBy(['applied_in' => SORT_ASC])
            ->one();
    }

}
