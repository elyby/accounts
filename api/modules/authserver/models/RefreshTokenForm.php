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
        $token = Yii::$app->tokens->parse($this->accessToken);
        $tokenReader = new TokenReader($token);
        if ($tokenReader->getMinecraftClientToken() !== $this->clientToken) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        $account = Account::findOne(['id' => $tokenReader->getAccountId()]);
        if ($account === null) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        $token = Yii::$app->tokensFactory->createForMinecraftAccount($account, $this->clientToken);

        // TODO: This behavior duplicates with the AuthenticationForm. Need to find a way to avoid duplication.
        /** @var OauthSession|null $minecraftOauthSession */
        $minecraftOauthSession = $account->getOauthSessions()
            ->andWhere(['client_id' => OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER])
            ->one();
        if ($minecraftOauthSession === null) {
            $minecraftOauthSession = new OauthSession();
            $minecraftOauthSession->account_id = $account->id;
            $minecraftOauthSession->client_id = OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER;
            $minecraftOauthSession->scopes = [P::MINECRAFT_SERVER_SESSION];
        }

        $minecraftOauthSession->last_used_at = time();
        Assert::true($minecraftOauthSession->save());

        return new AuthenticateData($account, $token->toString(), $this->clientToken, (bool)$this->requestUser);
    }

}
