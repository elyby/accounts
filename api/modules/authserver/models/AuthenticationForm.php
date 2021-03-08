<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\authentication\LoginForm;
use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\Module as Authserver;
use api\modules\authserver\validators\ClientTokenValidator;
use api\modules\authserver\validators\RequiredValidator;
use api\rbac\Permissions as P;
use common\helpers\Error as E;
use common\models\Account;
use common\models\OauthClient;
use common\models\OauthSession;
use Webmozart\Assert\Assert;
use Yii;

class AuthenticationForm extends ApiForm {

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $password;

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
            [['username', 'password', 'clientToken'], RequiredValidator::class],
            [['clientToken'], ClientTokenValidator::class],
            [['requestUser'], 'boolean'],
        ];
    }

    /**
     * @return AuthenticateData
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     */
    public function authenticate(): AuthenticateData {
        // This validating method will throw an exception in case when validation will not pass successfully
        $this->validate();

        Authserver::info("Trying to authenticate user by login = '{$this->username}'.");

        // The previous authorization server implementation used the nickname field instead of username,
        // so we keep such behavior
        $attribute = strpos($this->username, '@') === false ? 'nickname' : 'email';

        $password = $this->password;
        $totp = null;
        if (preg_match('/.{8,}:(\d{6})$/', $password, $matches) === 1) {
            $totp = $matches[1];
            $password = mb_substr($password, 0, -7); // :123456 - 7 chars
        }

        login:

        $loginForm = new LoginForm();
        $loginForm->login = $this->username;
        $loginForm->password = $password;
        $loginForm->totp = $totp;

        $isValid = $loginForm->validate();
        // Handle case when user's password matches the template for totp via password
        if (!$isValid && $totp !== null && $loginForm->getFirstError('password') === E::PASSWORD_INCORRECT) {
            $password = "{$password}:{$totp}";
            $totp = null;

            goto login;
        }

        if (!$isValid || $loginForm->getAccount()->status === Account::STATUS_DELETED) {
            $errors = $loginForm->getFirstErrors();
            if (isset($errors['login'])) {
                if ($errors['login'] === E::ACCOUNT_BANNED) {
                    Authserver::error("User with login = '{$this->username}' is banned");
                    throw new ForbiddenOperationException('This account has been suspended.');
                }

                Authserver::error("Cannot find user by login = '{$this->username}'");
            } elseif (isset($errors['password'])) {
                Authserver::error("User with login = '{$this->username}' passed wrong password.");
            } elseif (isset($errors['totp'])) {
                if ($errors['totp'] === E::TOTP_REQUIRED) {
                    Authserver::error("User with login = '{$this->username}' protected by two factor auth.");
                    throw new ForbiddenOperationException('Account protected with two factor auth.');
                }

                Authserver::error("User with login = '{$this->username}' passed wrong totp token");
            }

            throw new ForbiddenOperationException("Invalid credentials. Invalid {$attribute} or password.");
        }

        /** @var Account $account */
        $account = $loginForm->getAccount();
        $token = Yii::$app->tokensFactory->createForMinecraftAccount($account, $this->clientToken);
        $dataModel = new AuthenticateData($account, (string)$token, $this->clientToken, (bool)$this->requestUser);
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

}
