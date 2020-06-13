<?php
declare(strict_types=1);

namespace common\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property int         $id
 * @property string      $url
 * @property string|null $secret
 * @property string[]    $events
 * @property int         $created_at
 *
 * Behaviors:
 * @mixin TimestampBehavior
 */
class WebHook extends ActiveRecord {

    public static function tableName(): string {
        return 'webhooks';
    }

    public function behaviors(): array {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

}
