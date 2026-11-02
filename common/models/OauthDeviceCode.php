<?php
declare(strict_types=1);

namespace common\models;

use common\components\OAuth2\ScopesMapper;
use yii\behaviors\AttributeTypecastBehavior;
use yii\db\ActiveRecord;

/**
 * Fields:
 * @property string $device_code
 * @property string $user_code
 * @property string $client_id
 * @property array $scopes
 * @property int|null $account_id
 * @property bool|null $is_approved
 * @property int|null $last_polled_at
 * @property int $expires_at
 *
 * Relations:
 * @property-read OauthClient $client
 */
final class OauthDeviceCode extends ActiveRecord {

    public static function tableName(): string {
        return 'oauth_device_codes';
    }

    public function behaviors(): array {
        return [
            [
                'class' => AttributeTypecastBehavior::class,
                'attributeTypes' => [
                    'is_approved' => AttributeTypecastBehavior::TYPE_BOOLEAN,
                ],
                'typecastAfterSave' => true,
                'typecastAfterFind' => true,
            ],
        ];
    }

    public function getClient(): OauthClientQuery {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasOne(OauthClient::class, ['id' => 'client_id']);
    }

    /**
     * Previously the RBAC permissions names were stored instead of the public scopes names,
     * so the scopes are normalized on read.
     *
     * @return list<non-empty-string>
     */
    public function getScopes(): array {
        return ScopesMapper::normalizeScopes((array)$this->scopes);
    }

}
