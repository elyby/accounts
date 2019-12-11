<?php
namespace common\models;

use common\behaviors\PrimaryKeyValueBehavior;
use Ramsey\Uuid\Uuid;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is a temporary class where all the logic of the authserver.ely.by service.
 * Since the login and password were allowed there, and the format of storage of the issued tokens was different,
 * we need to keep the legacy logic and structure under it for the period until we finally migrate.
 *
 * Fields:
 * @property string  $access_token
 * @property string  $client_token
 * @property integer $account_id
 * @property integer $created_at
 * @property integer $updated_at
 *
 * Relations:
 * @property Account $account
 *
 * Behaviors:
 * @mixin TimestampBehavior
 * @mixin PrimaryKeyValueBehavior
 *
 * @deprecated This table is no longer used to store authorization information in Minecraft.
 * In time it will be empty (see the cleanup console command) and when it does, this model,
 * the table in the database and everything related to the old logic can be removed.
 */
class MinecraftAccessKey extends ActiveRecord {

    public const LIFETIME = 172800; // Ключ актуален в течение 2 дней

    public static function tableName(): string {
        return '{{%minecraft_access_keys}}';
    }

    public function behaviors(): array {
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

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function isExpired(): bool {
        return time() > $this->updated_at + self::LIFETIME;
    }

}
