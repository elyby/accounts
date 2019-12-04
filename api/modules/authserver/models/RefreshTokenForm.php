<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\AccessTokenValidator;
use api\modules\authserver\validators\RequiredValidator;
use common\models\Account;
use common\models\MinecraftAccessKey;
use Yii;

class RefreshTokenForm extends ApiForm {

    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var string
     */
    public $clientToken;

    public function rules(): array {
        return [
            [['accessToken', 'clientToken'], RequiredValidator::class],
            [['accessToken'], AccessTokenValidator::class, 'verifyExpiration' => false],
        ];
    }

    /**
     * @return AuthenticateData
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     */
    public function refresh(): AuthenticateData {
        $this->validate();
        $account = null;
        if (mb_strlen($this->accessToken) === 36) {
            /** @var MinecraftAccessKey $token */
            $token = MinecraftAccessKey::findOne([
                'access_token' => $this->accessToken,
                'client_token' => $this->clientToken,
            ]);
            if ($token !== null) {
                $account = $token->account;
            }
        } else {
            $token = Yii::$app->tokens->parse($this->accessToken);

            $encodedClientToken = $token->getClaim('ely-client-token');
            $clientToken = Yii::$app->tokens->decryptValue($encodedClientToken);
            if ($clientToken !== $this->clientToken) {
                throw new ForbiddenOperationException('Invalid token.');
            }

            $accountClaim = $token->getClaim('sub');
            $accountId = (int)explode('|', $accountClaim)[1];
            $account = Account::findOne(['id' => $accountId]);
        }

        if ($account === null) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if ($account->status === Account::STATUS_BANNED) {
            throw new ForbiddenOperationException('This account has been suspended.');
        }

        $token = Yii::$app->tokensFactory->createForMinecraftAccount($account, $this->clientToken);

        return new AuthenticateData($account, (string)$token, $this->clientToken);
    }

}
