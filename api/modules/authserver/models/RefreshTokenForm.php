<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\components\Tokens\TokenReader;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\AccessTokenValidator;
use api\modules\authserver\validators\RequiredValidator;
use common\models\Account;
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

    /**
     * @var string|bool
     */
    public $requestUser;

    public function rules(): array {
        return [
            [['accessToken', 'clientToken'], RequiredValidator::class],
            [['accessToken'], AccessTokenValidator::class, 'verifyExpiration' => false],
            [['requestUser'], 'boolean'],
        ];
    }

    /**
     * @return AuthenticateData
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     */
    public function refresh(): AuthenticateData {
        $this->validate();
        // The token has already been verified by the validation above, so this is just to read its claims
        $token = Yii::$app->tokens->verifyAccessToken($this->accessToken, verifyExpiration: false);
        $tokenReader = new TokenReader($token);
        if ($tokenReader->getMinecraftClientToken() !== $this->clientToken) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        $account = Account::findOne(['id' => $tokenReader->getAccountId()]);
        if ($account === null) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        /** @var OauthSession|null $minecraftOauthSession */
        $minecraftOauthSession = $account->getOauthSessions()
            ->andWhere(['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER])
            ->one();
        // The token's verification has already ensured that the session exists and isn't revoked
        Assert::notNull($minecraftOauthSession);
        $minecraftOauthSession->last_used_at = time();
        Assert::true($minecraftOauthSession->save());

        $token = Yii::$app->tokensFactory->createForMinecraftAccount($account, $this->clientToken);

        return new AuthenticateData($account, $token->toString(), $this->clientToken, (bool)$this->requestUser);
    }

}
