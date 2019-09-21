<?php
declare(strict_types=1);

namespace api\components\OAuth2\Traits;

trait ValidateScopesTrait {

    public function validateScopes($scopes, $redirectUri = null): array {
        return parent::validateScopes($scopes, $redirectUri = null);
    }

}
