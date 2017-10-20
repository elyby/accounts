<?php
namespace api\modules\session\models;

use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\IllegalArgumentException;
use api\modules\session\models\protocols\JoinInterface;
use api\modules\session\Module as Session;
use api\modules\session\validators\RequiredValidator;
use common\helpers\StringHelper;
use common\rbac\Permissions as P;
use common\models\Account;
use common\models\MinecraftAccessKey;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\base\ErrorException;
use yii\base\Model;
use yii\web\UnauthorizedHttpException;

class JoinForm extends Model {

    public $accessToken;
    public $selectedProfile;
    public $serverId;

    /**
     * @var Account|null
     */
    private $account;

    /**
     * @var JoinInterface
     */
    private $protocol;

    public function __construct(JoinInterface $protocol, array $config = []) {
        $this->protocol = $protocol;
        $this->accessToken = $protocol->getAccessToken();
        $this->selectedProfile = $protocol->getSelectedProfile();
        $this->serverId = $protocol->getServerId();

        parent::__construct($config);
    }

    public function rules() {
        return [
            [['accessToken', 'serverId'], RequiredValidator::class],
            [['accessToken', 'selectedProfile'], 'validateUuid'],
            [['accessToken'], 'validateAccessToken'],
        ];
    }

    public function join() {
        $serverId = $this->serverId;
        $accessToken = $this->accessToken;
        Session::info("User with access_token = '{$accessToken}' trying join to server with server_id = '{$serverId}'.");
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $sessionModel = new SessionModel($account->username, $serverId);
        if (!$sessionModel->save()) {
            throw new ErrorException('Cannot save join session model');
        }

        Session::info(
            "User with access_token = '{$accessToken}' and nickname = '{$account->username}' successfully joined to " .
            "server_id = '{$serverId}'."
        );

        return true;
    }

    public function validate($attributeNames = null, $clearErrors = true) {
        if (!$this->protocol->validate()) {
            throw new IllegalArgumentException();
        }

        return parent::validate($attributeNames, $clearErrors);
    }

    public function validateUuid($attribute) {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->$attribute === Uuid::NIL) {
            throw new IllegalArgumentException();
        }
    }

    /**
     * @throws \api\modules\session\exceptions\SessionServerException
     */
    public function validateAccessToken() {
        $accessToken = $this->accessToken;
        /** @var MinecraftAccessKey|null $accessModel */
        $accessModel = MinecraftAccessKey::findOne($accessToken);
        if ($accessModel !== null) {
            /** @var MinecraftAccessKey|\api\components\OAuth2\Entities\AccessTokenEntity $accessModel */
            if ($accessModel->isExpired()) {
                Session::error("User with access_token = '{$accessToken}' failed join by expired access_token.");
                throw new ForbiddenOperationException('Expired access_token.');
            }

            $account = $accessModel->account;
        } else {
            try {
                $identity = Yii::$app->user->loginByAccessToken($accessToken);
            } catch (UnauthorizedHttpException $e) {
                $identity = null;
            }

            if ($identity === null) {
                Session::error("User with access_token = '{$accessToken}' failed join by wrong access_token.");
                throw new ForbiddenOperationException('Invalid access_token.');
            }

            if (!Yii::$app->user->can(P::MINECRAFT_SERVER_SESSION)) {
                Session::error("User with access_token = '{$accessToken}' doesn't have enough scopes to make join.");
                throw new ForbiddenOperationException('The token does not have required scope.');
            }

            $account = $identity->getAccount();
        }

        $selectedProfile = $this->selectedProfile;
        $isUuid = StringHelper::isUuid($selectedProfile);
        if ($isUuid && $account->uuid !== $this->normalizeUUID($selectedProfile)) {
            Session::error(
                "User with access_token = '{$accessToken}' trying to join with identity = '{$selectedProfile}'," .
                " but access_token issued to account with id = '{$account->uuid}'."
            );
            throw new ForbiddenOperationException('Wrong selected_profile.');
        }

        if (!$isUuid && $account->username !== $selectedProfile) {
            Session::error(
                "User with access_token = '{$accessToken}' trying to join with identity = '{$selectedProfile}'," .
                " but access_token issued to account with username = '{$account->username}'."
            );
            throw new ForbiddenOperationException('Invalid credentials');
        }

        $this->account = $account;
    }

    protected function getAccount(): Account {
        return $this->account;
    }

    private function normalizeUUID(string $uuid): string {
        return Uuid::fromString($uuid)->toString();
    }

}
