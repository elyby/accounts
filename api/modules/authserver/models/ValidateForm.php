<?php
namespace api\modules\authserver\models;

use api\modules\authserver\exceptions\ForbiddenOperationException;
use api\modules\authserver\validators\RequiredValidator;
use common\models\MinecraftAccessKey;

class ValidateForm extends Form {

    public $accessToken;

    public function rules() {
        return [
            [['accessToken'], RequiredValidator::class],
        ];
    }

    public function validateToken() : bool {
        $this->validate();

        /** @var MinecraftAccessKey|null $result */
        $result = MinecraftAccessKey::findOne($this->accessToken);
        if ($result === null) {
            throw new ForbiddenOperationException('Invalid token.');
        }

        if ($result->isExpired()) {
            $result->delete();
            throw new ForbiddenOperationException('Token expired.');
        }

        return true;
    }

}
