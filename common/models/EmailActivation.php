<?php
namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * Поля модели:
 * @property string   $key
 * @property integer  $account_id
 * @property integer  $type
 * @property integer  $created_at
 *
 * Отношения:
 * @property Account $account
 *
 * Поведения:
 * @mixin TimestampBehavior
 *
 * TODO: у модели могут быть проблемы с уникальностью, т.к. key является первичным и не автоинкрементом
 * TODO: мб стоит ловить beforeCreate и именно там генерировать уникальный ключ для модели.
 * Но опять же нужно продумать, а как пробросить формат и обеспечить преемлемую уникальность.
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
