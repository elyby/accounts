<?php
namespace api\modules\authserver\models;

use api\modules\authserver\validators\RequiredValidator;
use common\models\MinecraftAccessKey;

class InvalidateForm extends Form {

    public $accessToken;
    public $clientToken;

    public function rules() {
        return [
            [['accessToken', 'clientToken'], RequiredValidator::class],
        ];
    }

    /**
     * @return bool
     * @throws \api\modules\authserver\exceptions\AuthserverException
     */
    public function invalidateToken() : bool {
        $this->validate();

        $token = MinecraftAccessKey::findOne([
            'access_token' => $this->accessToken,
            'client_token' => $this->clientToken,
        ]);

        if ($token !== null) {
            $token->delete();
        }

        return true;
    }

}
