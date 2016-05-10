<?php
namespace common\models;

use common\behaviors\EmailActivationExpirationBehavior;
use Yii;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

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
 * @mixin EmailActivationExpirationBehavior
 *
 * TODO: у модели могут быть проблемы с уникальностью, т.к. key является первичным и не автоинкрементом
 * TODO: мб стоит ловить beforeCreate и именно там генерировать уникальный ключ для модели.
 * Но опять же нужно продумать, а как пробросить формат и обеспечить преемлемую уникальность.
 */
class EmailActivation extends ActiveRecord {

    const TYPE_REGISTRATION_EMAIL_CONFIRMATION = 0;
    const TYPE_FORGOT_PASSWORD_KEY = 1;

    public static function tableName() {
        return '{{%email_activations}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
            'expirationBehavior' => [
                'class' => EmailActivationExpirationBehavior::class,
                'repeatTimeout' => 5 * 60,
                'expirationTimeout' => -1,
            ],
        ];
    }

    public function getAccount() {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    /**
     * @inheritdoc
     */
    public static function instantiate($row) {
        $type = ArrayHelper::getValue($row, 'type');
        if ($type === null) {
            return new static;
        }

        $classMap = self::getClassMap();
        if (!isset($classMap[$type])) {
            throw new InvalidConfigException('Unexpected type');
        }

        return new $classMap[$type];
    }

    public static function getClassMap() {
        return [
            self::TYPE_REGISTRATION_EMAIL_CONFIRMATION => confirmations\RegistrationConfirmation::class,
            self::TYPE_FORGOT_PASSWORD_KEY => confirmations\ForgotPassword::class,
        ];
    }

}
