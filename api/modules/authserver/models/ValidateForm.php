<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\base\ApiForm;
use api\modules\authserver\validators\AccessTokenValidator;
use api\modules\authserver\validators\RequiredValidator;

class ValidateForm extends ApiForm {

    /**
     * @var string
     */
    public $accessToken;

    public function rules(): array {
        return [
            [['accessToken'], RequiredValidator::class],
            [['accessToken'], AccessTokenValidator::class],
        ];
    }

    /**
     * @return bool
     * @throws \api\modules\authserver\exceptions\ForbiddenOperationException
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function validateToken(): bool {
        return $this->validate();
    }

}
