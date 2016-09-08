<?php
namespace common\models;

use common\behaviors\PrimaryKeyValueBehavior;
use Ramsey\Uuid\Uuid;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Это временный класс, куда мигрирует вся логика ныне существующего authserver.ely.by.
 * Поскольку там допускался вход по логину и паролю, а формат хранения выданных токенов был
 * иным, то на период, пока мы окончательно не мигрируем, нужно сохранить старую логику
 * и структуру под неё.
 *
 * Поля модели:
 * @property string  $access_token
 * @property string  $client_token
 * @property integer $account_id
 * @property integer $created_at
 * @property integer $updated_at
 *
 * Отношения:
 * @property Account $account
 *
 * Поведения:
 * @mixin TimestampBehavior
 * @mixin PrimaryKeyValueBehavior
 */
class MinecraftAccessKey extends ActiveRecord {

    const LIFETIME = 172800; // Ключ актуален в течение 2 дней

    public static function tableName() {
        return '{{%minecraft_access_keys}}';
    }

    public function behaviors() {
        return [
            [
                'class' => TimestampBehavior::class,
            ],
            [
                'class' => PrimaryKeyValueBehavior::class,
                'value' => function() {
                    return Uuid::uuid4()->toString();
                },
            ],
        ];
    }

    public function getAccount() : ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function isExpired() : bool {
        return time() > $this->updated_at + self::LIFETIME;
    }

}
