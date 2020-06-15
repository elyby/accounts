<?php
declare(strict_types=1);

namespace api\modules\authserver\validators;

use api\components\Tokens\TokenReader;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use Carbon\Carbon;
use common\models\Account;
use common\models\MinecraftAccessKey;
use Exception;
use Yii;
use yii\validators\Validator;

class AccessTokenValidator extends Validator {

    private const INVALID_TOKEN = 'Invalid token.';
    private const TOKEN_EXPIRED = 'Token expired.';

    public bool $verifyExpiration = true;

    public bool $verifyAccount = true;

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
            throw new ForbiddenOperationException(self::INVALID_TOKEN);
        }

        if (!Yii::$app->tokens->verify($token)) {
            throw new ForbiddenOperationException(self::INVALID_TOKEN);
        }

        if ($this->verifyExpiration && $token->isExpired(Carbon::now())) {
            throw new ForbiddenOperationException(self::TOKEN_EXPIRED);
        }

        if ($this->verifyAccount && !$this->validateAccount((new TokenReader($token))->getAccountId())) {
            throw new ForbiddenOperationException(self::INVALID_TOKEN);
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
            throw new ForbiddenOperationException(self::INVALID_TOKEN);
        }

        if ($this->verifyExpiration && $result->isExpired()) {
            throw new ForbiddenOperationException(self::TOKEN_EXPIRED);
        }

        if ($this->verifyAccount && !$this->validateAccount($result->account_id)) {
            throw new ForbiddenOperationException(self::INVALID_TOKEN);
        }

        return null;
    }

    private function validateAccount(int $accountId): bool {
        /** @var Account|null $account */
        $account = Account::find()->excludeDeleted()->andWhere(['id' => $accountId])->one();

        return $account !== null && $account->status !== Account::STATUS_BANNED;
    }

}
