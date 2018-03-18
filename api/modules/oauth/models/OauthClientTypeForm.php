<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use common\models\OauthClient;

interface OauthClientTypeForm {

    public function load($data): bool;

    public function validate(): bool;

    public function getValidationErrors(): array;

    public function applyToClient(OauthClient $client): void;

}
