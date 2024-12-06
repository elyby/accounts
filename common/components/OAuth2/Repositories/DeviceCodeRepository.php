<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use Carbon\CarbonImmutable;
use common\components\OAuth2\Entities\ClientEntity;
use common\components\OAuth2\Entities\DeviceCodeEntity;
use common\components\OAuth2\Entities\ScopeEntity;
use common\models\OauthDeviceCode;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\DeviceCodeRepositoryInterface;
use Webmozart\Assert\Assert;
use yii\db\Exception;

final class DeviceCodeRepository implements DeviceCodeRepositoryInterface {

    public function getNewDeviceCode(): DeviceCodeEntityInterface {
        return new DeviceCodeEntity();
    }

    public function persistDeviceCode(DeviceCodeEntityInterface $deviceCodeEntity): void {
        $model = $this->findModelByDeviceCode($deviceCodeEntity->getIdentifier()) ?? new OauthDeviceCode();
        $model->device_code = $deviceCodeEntity->getIdentifier();
        $model->user_code = $deviceCodeEntity->getUserCode();
        $model->client_id = $deviceCodeEntity->getClient()->getIdentifier();
        $model->scopes = array_map(fn($scope) => $scope->getIdentifier(), $deviceCodeEntity->getScopes());
        $model->last_polled_at = $deviceCodeEntity->getLastPolledAt()?->getTimestamp();
        $model->expires_at = $deviceCodeEntity->getExpiryDateTime()->getTimestamp();
        if ($deviceCodeEntity->getUserIdentifier() !== null) {
            $model->account_id = (int)$deviceCodeEntity->getUserIdentifier();
            $model->is_approved = $deviceCodeEntity->getUserApproved();
        }

        try {
            Assert::true($model->save());
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'duplicate')) {
                throw UniqueTokenIdentifierConstraintViolationException::create();
            }

            throw $e;
        }
    }

    public function getDeviceCodeEntityByDeviceCode(string $deviceCodeEntity): ?DeviceCodeEntityInterface {
        $model = $this->findModelByDeviceCode($deviceCodeEntity);
        if ($model === null) {
            return null;
        }

        $entity = $this->getNewDeviceCode();
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

    public function revokeDeviceCode(string $codeId): void {
        $this->findModelByDeviceCode($codeId)?->delete();
    }

    public function isDeviceCodeRevoked(string $codeId): bool {
        return $this->findModelByDeviceCode($codeId) === null;
    }

    private function findModelByDeviceCode(string $deviceCode): ?OauthDeviceCode {
        return OauthDeviceCode::findOne(['device_code' => $deviceCode]);
    }

}
