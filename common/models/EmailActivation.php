<?php
namespace common\models;

use common\behaviors\DataBehavior;
use common\behaviors\EmailActivationExpirationBehavior;
use common\behaviors\PrimaryKeyValueBehavior;
use common\components\UserFriendlyRandomKey;
use yii\base\InvalidConfigException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Поля модели:
 * @property string  $key
 * @property integer $account_id
 * @property integer $type
 * @property string  $_data
 * @property integer $created_at
 *
 * Отношения:
 * @property Account $account
 *
 * Поведения:
 * @mixin TimestampBehavior
 * @mixin EmailActivationExpirationBehavior
 * @mixin DataBehavior
 */
class EmailActivation extends ActiveRecord {

    const TYPE_REGISTRATION_EMAIL_CONFIRMATION = 0;
    const TYPE_FORGOT_PASSWORD_KEY = 1;
    const TYPE_CURRENT_EMAIL_CONFIRMATION = 2;
    const TYPE_NEW_EMAIL_CONFIRMATION = 3;

    public static function tableName() {
        return '{{%email_activations}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
            [
                'class' => PrimaryKeyValueBehavior::class,
                'value' => function() {
                    return UserFriendlyRandomKey::make();
                },
            ],
            'expirationBehavior' => [
                'class' => EmailActivationExpirationBehavior::class,
                'repeatTimeout' => 5 * 60, // 5m
                'expirationTimeout' => -1,
            ],
            'dataBehavior' => [
                'class' => DataBehavior::class,
                'attribute' => '_data',
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
            return parent::instantiate($row);
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
            self::TYPE_FORGOT_PASSWORD_KEY             => confirmations\ForgotPassword::class,
            self::TYPE_CURRENT_EMAIL_CONFIRMATION      => confirmations\CurrentEmailConfirmation::class,
            self::TYPE_NEW_EMAIL_CONFIRMATION          => confirmations\NewEmailConfirmation::class,
        ];
    }

}
