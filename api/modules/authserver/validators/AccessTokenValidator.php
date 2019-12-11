<?php
declare(strict_types=1);

namespace api\modules\authserver\validators;

use api\modules\authserver\exceptions\ForbiddenOperationException;
use Carbon\Carbon;
use common\models\MinecraftAccessKey;
use Exception;
use Lcobucci\JWT\ValidationData;
use Yii;
use yii\validators\Validator;

class AccessTokenValidator extends Validator {

    /**
     * @var bool
     */
    public $verifyExpiration = true;

    /**
     * @param string $value
     *
     * @return array|null
     * @throws ForbiddenOperationException
     */
    protected function validateValue($value): ?array {
        if (mb_strlen($value) === 36) {
            return $this->validateLegacyToken($value);
        }

        try {
            $token = Yii::$app->tokens->parse($value);
        } catch (Exception $e) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if (!Yii::$app->tokens->verify($token)) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if ($this->verifyExpiration && !$token->validate(new ValidationData(Carbon::now()->getTimestamp()))) {
            throw new ForbiddenOperationException('Token expired.');
        }

        return null;
    }

    /**
     * @param string $value
     *
     * @return array|null
     * @throws ForbiddenOperationException
     */
    private function validateLegacyToken(string $value): ?array {
        /** @var MinecraftAccessKey|null $result */
        $result = MinecraftAccessKey::findOne(['access_token' => $value]);
        if ($result === null) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if ($this->verifyExpiration && $result->isExpired()) {
            throw new ForbiddenOperationException('Token expired.');
        }

        return null;
    }

}
