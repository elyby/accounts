<?php
declare(strict_types=1);

namespace common\components\OAuth2\ResponseTypes;

use common\components\OAuth2\CryptTrait;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use common\models\Account;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse as BaseBearerTokenResponse;
use Yii;

final class BearerTokenResponse extends BaseBearerTokenResponse {
    use CryptTrait;

    /**
     * @var non-empty-string|null
     */
    private ?string $nonce = null;

    /**
     * @param non-empty-string|null $nonce
     */
    public function setNonce(?string $nonce): void {
        $this->nonce = $nonce;
    }

    /**
     * The granted scopes may differ from the requested ones, so they're returned in the response (RFC 6749 section 5.1).
     * When the openid scope is granted, the id_token is returned as well (OpenID Connect Core 1.0 section 3.1.3.3).
     *
     * @return array<string, string>
     * @throws OAuthServerException
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array {
        $scopes = array_map(fn(ScopeEntityInterface $scope): string => $scope->getIdentifier(), $accessToken->getScopes());
        if (empty($scopes)) {
            return [];
        }

        $params = [
            'scope' => implode(' ', $scopes),
        ];

        // The tokens without a user (client_credentials) have no subject for the id_token
        $userIdentifier = $accessToken->getUserIdentifier();
        if ($userIdentifier !== null && in_array(PublicScopeRepository::OPENID, $scopes, true)) {
            $account = Account::findOne(['id' => (int)$userIdentifier]);
            if ($account === null) {
                throw OAuthServerException::invalidGrant('The user could not be found');
            }

            $clientId = $accessToken->getClient()->getIdentifier();
            $params['id_token'] = Yii::$app->tokensFactory->createIdToken($account, $clientId, $scopes, $this->nonce)->toString();
        }

        return $params;
    }

}
