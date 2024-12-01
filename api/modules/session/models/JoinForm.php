<?php
declare(strict_types=1);

namespace api\modules\session\models;

use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\IllegalArgumentException;
use api\modules\session\models\protocols\JoinInterface;
use api\modules\session\Module as Session;
use api\modules\session\validators\RequiredValidator;
use api\rbac\Permissions as P;
use common\helpers\StringHelper;
use common\models\Account;
use Ramsey\Uuid\Uuid;
use Webmozart\Assert\Assert;
use Yii;
use yii\base\Model;
use yii\web\UnauthorizedHttpException;

class JoinForm extends Model {

    public mixed $accessToken = null;

    public mixed $selectedProfile = null;

    public mixed $serverId = null;

    /**
     * @var Account|null
     */
    private ?Account $account = null;

    public function __construct(
        private JoinInterface $protocol,
        array $config = [],
    ) {
        parent::__construct($config);
        $this->accessToken = $this->protocol->getAccessToken();
        $this->selectedProfile = $this->protocol->getSelectedProfile();
        $this->serverId = $this->protocol->getServerId();
    }

    public function rules(): array {
        return [
            [['accessToken', 'serverId'], RequiredValidator::class],
            [['accessToken', 'selectedProfile'], $this->validateUuid(...)],
            [['accessToken'], $this->validateAccessToken(...)],
        ];
    }

    /**
     * @throws IllegalArgumentException
     * @throws ForbiddenOperationException
     */
    public function join(): bool {
        $serverId = $this->serverId;
        $accessToken = $this->accessToken;
        Session::info("User with access_token = '{$accessToken}' trying join to server with server_id = '{$serverId}'.");
        Yii::$app->statsd->inc('sessionserver.join.attempt');
        if (!$this->validate()) {
            return false;
        }

        $account = $this->account;
        $sessionModel = new SessionModel($account->username, $serverId);
        Assert::true($sessionModel->save());

        Session::info("User with access_token = '{$accessToken}' and nickname = '{$account->username}' successfully joined to server_id = '{$serverId}'.");
        Yii::$app->statsd->inc('sessionserver.join.success');

        return true;
    }

    /**
     * @param string $attributeNames
     * @param bool $clearErrors
     *
     * @return bool
     * @throws IllegalArgumentException
     */
    public function validate($attributeNames = null, $clearErrors = true): bool {
        if (!$this->protocol->validate()) {
            throw new IllegalArgumentException();
        }

        return parent::validate($attributeNames, $clearErrors);
    }

    /**
     * @param string $attribute
     *
     * @throws IllegalArgumentException
     */
    private function validateUuid(string $attribute): void {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->$attribute === Uuid::NIL) {
            throw new IllegalArgumentException();
        }
    }

    /**
     * @throws \api\modules\session\exceptions\ForbiddenOperationException
     */
    private function validateAccessToken(): void {
        $accessToken = $this->accessToken;
        try {
            $identity = Yii::$app->user->loginByAccessToken($accessToken);
        } catch (UnauthorizedHttpException $e) {
            if ($e->getMessage() === 'Token expired') {
                throw new ForbiddenOperationException('Expired access_token.', 0, $e);
            }

            $identity = null;
        }

        if ($identity === null) {
            Session::error("User with access_token = '{$accessToken}' failed join by wrong access_token.");
            Yii::$app->statsd->inc('sessionserver.join.fail_wrong_token');

            throw new ForbiddenOperationException('Invalid access_token.');
        }

        Yii::$app->statsd->inc('sessionserver.authentication.oauth2');
        if (!Yii::$app->user->can(P::MINECRAFT_SERVER_SESSION)) {
            Session::error("User with access_token = '{$accessToken}' doesn't have enough scopes to make join.");
            Yii::$app->statsd->inc('sessionserver.authentication.oauth2_not_enough_scopes');

            throw new ForbiddenOperationException('The token does not have required scope.');
        }

        /** @var Account $account */
        $account = $identity->getAccount();

        $selectedProfile = $this->selectedProfile;
        $isUuid = StringHelper::isUuid($selectedProfile);
        if ($isUuid && $account->uuid !== $this->normalizeUUID($selectedProfile)) {
            Session::error("User with access_token = '{$accessToken}' trying to join with identity = '{$selectedProfile}', but access_token issued to account with id = '{$account->uuid}'.");
            Yii::$app->statsd->inc('sessionserver.join.fail_uuid_mismatch');

            throw new ForbiddenOperationException('Wrong selected_profile.');
        }

        if (!$isUuid && mb_strtolower($account->username) !== mb_strtolower($selectedProfile)) {
            Session::error("User with access_token = '{$accessToken}' trying to join with identity = '{$selectedProfile}', but access_token issued to account with username = '{$account->username}'.");
            Yii::$app->statsd->inc('sessionserver.join.fail_username_mismatch');

            throw new ForbiddenOperationException('Invalid credentials');
        }

        if ($account->status === Account::STATUS_DELETED) {
            throw new ForbiddenOperationException('Invalid credentials');
        }

        $this->account = $account;
    }

    private function normalizeUUID(string $uuid): string {
        return Uuid::fromString($uuid)->toString();
    }

}
