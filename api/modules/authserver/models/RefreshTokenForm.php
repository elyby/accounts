<?php
namespace api\modules\authserver\models;

use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\models\MinecraftAccessKey;

class RefreshTokenForm extends Form {

    public $accessToken;
    public $clientToken;
    public $selectedProfile;
    public $requestUser;

    public function rules() {
        return [
            [['accessToken', 'clientToken', 'selectedProfile', 'requestUser'], RequiredValidator::class],
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

        $accessToken->refreshPrimaryKeyValue();
        $accessToken->update();

        $dataModel = new AuthenticateData($accessToken);

        return $dataModel;
    }

}
