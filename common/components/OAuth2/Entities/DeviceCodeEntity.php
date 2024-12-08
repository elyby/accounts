<?php
declare(strict_types=1);

namespace common\components\OAuth2\Entities;

use Carbon\CarbonImmutable;
use common\models\OauthDeviceCode;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\DeviceCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

final class DeviceCodeEntity implements DeviceCodeEntityInterface {
    use EntityTrait;
    use TokenEntityTrait;
    use DeviceCodeTrait;

    public static function fromModel(OauthDeviceCode $model): self {
        $entity = new self();
        $entity->setIdentifier($model->device_code); // @phpstan-ignore argument.type
        $entity->setUserCode($model->user_code);
        $entity->setClient(ClientEntity::fromModel($model->client));
        $entity->setExpiryDateTime(CarbonImmutable::createFromTimestampUTC($model->expires_at));
        foreach ($model->scopes as $scope) {
            $entity->addScope(new ScopeEntity($scope));
        }

        if ($model->account_id !== null) {
            $entity->setUserIdentifier((string)$model->account_id);
            $entity->setUserApproved((bool)$model->is_approved === true);
        }

        if ($model->last_polled_at !== null) {
            $entity->setLastPolledAt(CarbonImmutable::createFromTimestampUTC($model->last_polled_at));
        }

        return $entity;
    }

}
