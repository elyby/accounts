<?php
declare(strict_types=1);

namespace common\components\OAuth2\Repositories;

use common\components\OAuth2\Entities\DeviceCodeEntity;
use common\models\OauthDeviceCode;
use League\OAuth2\Server\Entities\DeviceCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use Webmozart\Assert\Assert;
use yii\db\Exception;

final class DeviceCodeRepository implements ExtendedDeviceCodeRepositoryInterface {

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

        return DeviceCodeEntity::fromModel($model);
    }

    public function getDeviceCodeEntityByUserCode(string $userCode): ?DeviceCodeEntityInterface {
        $model = OauthDeviceCode::findOne(['user_code' => $userCode]);
        if ($model === null) {
            return null;
        }

        return DeviceCodeEntity::fromModel($model);
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
