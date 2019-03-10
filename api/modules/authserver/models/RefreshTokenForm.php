<?php
namespace api\modules\authserver\models;

use api\models\base\ApiForm;
use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\models\Account;
use common\models\MinecraftAccessKey;

class RefreshTokenForm extends ApiForm {

    public $accessToken;

    public $clientToken;

    public function rules() {
        return [
            [['accessToken', 'clientToken'], RequiredValidator::class],
        ];
    }

    /**
     * @return AuthenticateData
     * @throws \api\modules\authserver\exceptions\AuthserverException
     */
    public function refresh() {
        $this->validate();

        /** @var MinecraftAccessKey|null $accessToken */
        $accessToken = MinecraftAccessKey::findOne([
            'access_token' => $this->accessToken,
            'client_token' => $this->clientToken,
        ]);
        if ($accessToken === null) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if ($accessToken->account->status === Account::STATUS_BANNED) {
            throw new ForbiddenOperationException('This account has been suspended.');
        }

        $accessToken->refreshPrimaryKeyValue();
        $accessToken->update();

        return new AuthenticateData($accessToken);
    }

}
