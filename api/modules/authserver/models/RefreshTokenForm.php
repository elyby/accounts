<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\components\Tokens\TokenReader;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\AccessTokenValidator;
use api\modules\authserver\validators\RequiredValidator;
use api\rbac\Permissions as P;
use common\models\Account;
use common\models\MinecraftAccessKey;
use common\models\OauthClient;
use common\models\OauthSession;
use Webmozart\Assert\Assert;
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
            $tokenReader = new TokenReader($token);
            if ($tokenReader->getMinecraftClientToken() !== $this->clientToken) {
                throw new ForbiddenOperationException('Invalid token.');
            }

            $account = Account::findOne(['id' => $tokenReader->getAccountId()]);
        }

        if ($account === null || $account->status === Account::STATUS_DELETED) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if ($account->status === Account::STATUS_BANNED) {
            throw new ForbiddenOperationException('This account has been suspended.');
        }

        $token = Yii::$app->tokensFactory->createForMinecraftAccount($account, $this->clientToken);

        // TODO: This behavior duplicates with the AuthenticationForm. Need to find a way to avoid duplication.
        /** @var OauthSession|null $minecraftOauthSession */
        $hasMinecraftOauthSession = $account->getOauthSessions()
            ->andWhere(['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER])
            ->exists();
        if ($hasMinecraftOauthSession === false) {
            $minecraftOauthSession = new OauthSession();
            $minecraftOauthSession->account_id = $account->id;
            $minecraftOauthSession->client_id = OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER;
            $minecraftOauthSession->scopes = [P::MINECRAFT_SERVER_SESSION];
            Assert::true($minecraftOauthSession->save());
        }

        return new AuthenticateData($account, (string)$token, $this->clientToken);
    }

}
