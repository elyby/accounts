<?php
namespace api\modules\session\models;

use api\modules\session\exceptions\ForbiddenOperationException;
use api\modules\session\exceptions\IllegalArgumentException;
use api\modules\session\Module as Session;
use api\modules\session\validators\RequiredValidator;
use common\models\OauthScope as S;
use common\validators\UuidValidator;
use common\models\Account;
use common\models\MinecraftAccessKey;
use Yii;
use yii\base\ErrorException;
use yii\web\UnauthorizedHttpException;

class JoinForm extends Form {

    public $accessToken;
    public $selectedProfile;
    public $serverId;

    private $account;

    public function rules() {
        return [
            [['accessToken', 'selectedProfile', 'serverId'], RequiredValidator::class],
            [['accessToken', 'selectedProfile'], 'validateUuid'],
            [['accessToken'], 'validateAccessToken'],
        ];
    }

    public function join() {
        Session::info(
            "User with access_token = '{$this->accessToken}' trying join to server with server_id = " .
            "'{$this->serverId}'."
        );
        if (!$this->validate()) {
            return false;
        }

        $account = $this->getAccount();
        $sessionModel = new SessionModel($account->username, $this->serverId);
        if (!$sessionModel->save()) {
            throw new ErrorException('Cannot save join session model');
        }

        Session::info(
            "User with access_token = '{$this->accessToken}' and nickname = '{$account->username}' successfully " .
            "joined to server_id = '{$this->serverId}'."
        );

        return true;
    }

    public function validateUuid($attribute) {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $validator = new UuidValidator();
        $validator->validateAttribute($this, $attribute);

        if ($this->hasErrors($attribute)) {
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
        if ($accessModel === null) {
            try {
                $identity = Yii::$app->apiUser->loginByAccessToken($accessToken);
            } catch (UnauthorizedHttpException $e) {
                $identity = null;
            }

            if ($identity === null) {
                Session::error("User with access_token = '{$accessToken}' failed join by wrong access_token.");
                throw new ForbiddenOperationException('Invalid access_token.');
            }

            if (!Yii::$app->apiUser->can(S::MINECRAFT_SERVER_SESSION)) {
                Session::error("User with access_token = '{$accessToken}' doesn't have enough scopes to make join.");
                throw new ForbiddenOperationException('The token does not have required scope.');
            }

            $accessModel = $identity->getAccessToken();
            $account = $identity->getAccount();
        } else {
            $account = $accessModel->account;
        }

        /** @var MinecraftAccessKey|\common\models\OauthAccessToken $accessModel */
        if ($accessModel->isExpired()) {
            Session::error("User with access_token = '{$accessToken}' failed join by expired access_token.");
            throw new ForbiddenOperationException('Expired access_token.');
        }

        if ($account->uuid !== $this->selectedProfile) {
            Session::error(
                "User with access_token = '{$accessToken}' trying to join with identity = '{$this->selectedProfile}'," .
                " but access_token issued to account with id = '{$account->uuid}'."
            );
            throw new ForbiddenOperationException('Wrong selected_profile.');
        }

        $this->account = $account;
    }

    /**
     * @return Account|null
     */
    protected function getAccount() {
        return $this->account;
    }

}
