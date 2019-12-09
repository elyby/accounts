<?php
declare(strict_types=1);

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property string         $id
 * @property string         $secret
 * @property string         $type
 * @property string         $name
 * @property string         $description
 * @property string|null    $redirect_uri
 * @property string         $website_url
 * @property string         $minecraft_server_ip
 * @property integer        $account_id
 * @property bool           $is_trusted
 * @property bool           $is_deleted
 * @property int            $created_at
 *
 * Behaviors:
 * @property Account|null $account
 * @property OauthSession[] $sessions
 */
class OauthClient extends ActiveRecord {

    public const TYPE_APPLICATION = 'application';
    public const TYPE_MINECRAFT_SERVER = 'minecraft-server';

    public static function tableName(): string {
        return 'oauth_clients';
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function generateSecret(): void {
        $this->secret = Yii::$app->security->generateRandomString(64);
    }

    public function getAccount(): ActiveQuery {
        return $this->hasOne(Account::class, ['id' => 'account_id']);
    }

    public function getSessions(): ActiveQuery {
        return $this->hasMany(OauthSession::class, ['client_id' => 'id']);
    }

    public static function find(): OauthClientQuery {
        return Yii::createObject(OauthClientQuery::class, [static::class]);
    }

}
