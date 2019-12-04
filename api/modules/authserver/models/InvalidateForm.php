<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use api\models\base\ApiForm;
use api\modules\authserver\validators\RequiredValidator;

class InvalidateForm extends ApiForm {

    /**
     * @var string
     */
    public $accessToken;

    /**
     * @var string
     */
    public $clientToken;

    public function rules(): array {
        return [
            [['accessToken', 'clientToken'], RequiredValidator::class],
        ];
    }

    /**
     * @return bool
     * @throws \api\modules\authserver\exceptions\IllegalArgumentException
     */
    public function invalidateToken(): bool {
        $this->validate();

        // We're can't invalidate access token because it's not stored in our database

        return true;
    }

}
