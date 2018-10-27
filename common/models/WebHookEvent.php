<?php
declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQueryInterface;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property int    $webhook_id
 * @property string $event_type
 *
 * Relations:
 * @property WebHook $webhook
 */
class WebHookEvent extends ActiveRecord {

    public static function tableName(): string {
        return '{{%webhooks_events}}';
    }

    public function getWebhook(): ActiveQueryInterface {
        return $this->hasOne(WebHook::class, ['id' => 'webhook_id']);
    }

}
