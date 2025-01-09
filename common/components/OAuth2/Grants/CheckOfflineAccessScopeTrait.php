<?php
declare(strict_types=1);

namespace common\components\OAuth2\Grants;

use common\components\OAuth2\Events\RequestedRefreshToken;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use League\OAuth2\Server\EventEmitting\EventEmitter;

trait CheckOfflineAccessScopeTrait {

    abstract public function getEmitter(): EventEmitter;

    /**
     * @param \League\OAuth2\Server\Entities\ScopeEntityInterface[] $scopes
     */
    protected function checkOfflineAccessScope(array $scopes = []): void {
        foreach ($scopes as $i => $scope) {
            if ($scope->getIdentifier() === PublicScopeRepository::OFFLINE_ACCESS) {
                unset($scopes[$i]);
                $this->getEmitter()->emit(new RequestedRefreshToken('refresh_token_requested'));
            }
        }
    }

}
