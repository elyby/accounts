<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\components\Tokens\TokensFactory;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\Module as Authserver;
use api\modules\authserver\validators\ClientTokenValidator;
use api\modules\authserver\validators\RequiredValidator;
use api\rbac\Permissions as P;
use common\components\Authentication\Entities\Credentials;
use common\components\Authentication\Exceptions;
use common\components\Authentication\Exceptions\AuthenticationException;
use common\components\Authentication\LoginServiceInterface;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;

final class AuthenticationForm extends ApiForm {

    public mixed $username = null;

    public mixed $password = null;

    public mixed $clientToken = null;

    public mixed $requestUser = null;

    public function __construct(
        private readonly LoginServiceInterface $loginService,
        private readonly TokensFactory $tokensFactory,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function rules(): array {
        return [
            [['username', 'password'], RequiredValidator::class],
            [['clientToken'], ClientTokenValidator::class],
            [['requestUser'], 'boolean'],
        ];
    }

    /**
     * @throws ForbiddenOperationException
     */
    public function authenticate(): AuthenticateData {
        // This validating method will throw an exception in case when validation will not pass successfully
        $this->validate();

        Authserver::info("Trying to authenticate user by login = '{$this->username}'.");

        $password = (string)$this->password;
        $totp = null;
        if (preg_match('/.{8,}:(\d{6})$/', $password, $matches) === 1) {
            $totp = $matches[1];
            $password = mb_substr($password, 0, -7); // :123456 - 7 chars
        }

        login:

        $credentials = new Credentials(
            login: (string)$this->username,
            password: $password,
            totp: $totp,
        );

        try {
            $result = $this->loginService->loginByCredentials($credentials);
        } catch (Exceptions\InvalidPasswordException $e) {
            if ($totp !== null) {
                $password = $this->password;
                goto login;
            }

            $this->convertAuthenticationException($e);
        } catch (AuthenticationException $e) {
            $this->convertAuthenticationException($e);
        }

        $account = $result->account;
        if ($account->status === Account::STATUS_DELETED) {
            throw new ForbiddenOperationException('Invalid credentials. Invalid username or password.');
        }

        $clientToken = $this->clientToken ?: Uuid::uuid4()->toString();
        $token = $this->tokensFactory->createForMinecraftAccount($account, $clientToken);
        $dataModel = new AuthenticateData($account, $token->toString(), $clientToken, (bool)$this->requestUser);
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

        Authserver::info("User with id = {$account->id}, username = '{$account->username}' and email = '{$account->email}' successfully logged in.");

        return $dataModel;
    }

    /**
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     */
    private function convertAuthenticationException(AuthenticationException $e): never {
        throw match ($e::class) {
            Exceptions\AccountBannedException::class => new ForbiddenOperationException('This account has been suspended.'),
            Exceptions\TotpRequiredException::class => new ForbiddenOperationException('Account protected with two factor auth.'),
            default => new ForbiddenOperationException('Invalid credentials. Invalid username or password.'),
        };
    }

}
