<?php
declare(strict_types=1);

namespace api\components\OAuth2\Entities;

use api\components\OAuth2\Repositories\PublicScopeRepository;
use api\rbac\Permissions;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use Yii;

class AccessTokenEntity implements AccessTokenEntityInterface {
    use EntityTrait;
    use TokenEntityTrait {
        getExpiryDateTime as parentGetExpiryDateTime;
    }

    /**
     * There is no need to store offline_access scope in the resulting access_token.
     * We cannot remove it from the token because otherwise we won't be able to form a refresh_token.
     * That's why we delete offline_access before creating the token and then return it back.
     *
     * @return string
     */
    public function __toString(): string {
        $scopes = $this->scopes;
        $this->scopes = array_filter($this->scopes, function(ScopeEntityInterface $scope): bool {
            return $scope->getIdentifier() !== PublicScopeRepository::OFFLINE_ACCESS;
        });

        $token = Yii::$app->tokensFactory->createForOAuthClient($this);

        $this->scopes = $scopes;

        return (string)$token;
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void {
        // We use a general-purpose component to build JWT tokens, so there is no need to keep the key
    }

    public function getExpiryDateTime(): DateTimeImmutable {
        $expiryTime = $this->parentGetExpiryDateTime();
        if ($this->hasScope(PublicScopeRepository::CHANGE_SKIN) || $this->hasScope(Permissions::OBTAIN_ACCOUNT_EMAIL)) {
            $expiryTime = min($expiryTime, CarbonImmutable::now()->addHour());
        }

        return $expiryTime;
    }

    private function hasScope(string $scopeIdentifier): bool {
        foreach ($this->getScopes() as $scope) {
            if ($scope->getIdentifier() === $scopeIdentifier) {
                return true;
            }
        }

        return false;
    }

}
