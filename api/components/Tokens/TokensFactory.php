<?php
declare(strict_types=1);

namespace api\components\Tokens;

use api\rbac\Roles as R;
use Carbon\Carbon;
use common\components\OAuth2\OpenIdClaims;
use common\components\OAuth2\Repositories\PublicScopeRepository;
use common\models\Account;
use common\models\AccountSession;
use DateTime;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Webmozart\Assert\Assert;
use Yii;
use yii\base\Component;

class TokensFactory extends Component {

    public const string SUB_ACCOUNT_PREFIX = 'ely|';

    public function createForWebAccount(Account $account, AccountSession $session = null): UnencryptedToken {
        $payloads = [
            'sub' => $this->buildSub($account->id),
            'exp' => Carbon::now()->addHour()->toDateTimeImmutable(),
            'scope' => $this->prepareScopes([R::ACCOUNTS_WEB_USER]),
        ];
        if ($session === null) {
            // If we don't remember a session, the token should live longer
            // so that the session doesn't end while working with the account
            $payloads['exp'] = Carbon::now()->addDays(7)->toDateTimeImmutable();
        } else {
            $payloads['jti'] = (string)$session->id;
        }

        return Yii::$app->tokens->create($payloads);
    }

    public function createForOAuthClient(AccessTokenEntityInterface $accessToken): UnencryptedToken {
        $payloads = [
            'client_id' => $accessToken->getClient()->getIdentifier(),
            'scope' => $this->prepareScopes($accessToken->getScopes()),
        ];
        if ($accessToken->getExpiryDateTime() > new DateTime()) {
            $payloads['exp'] = $accessToken->getExpiryDateTime();
        }

        if ($accessToken->getUserIdentifier() !== null) {
            $payloads['sub'] = $this->buildSub((int)$accessToken->getUserIdentifier());
        }

        return Yii::$app->tokens->create($payloads);
    }

    /**
     * OpenID Connect ID Token (OpenID Connect Core 1.0 section 2).
     *
     * The token doesn't contain the client_id and the scope claims and its sub claim has a different format,
     * so it can't be used as the access token.
     *
     * @param non-empty-string $clientId
     * @param non-empty-string[] $scopes
     * @param non-empty-string|null $nonce
     */
    public function createIdToken(Account $account, string $clientId, array $scopes, ?string $nonce): UnencryptedToken {
        $issuer = Yii::$app->request->getHostInfo();
        Assert::stringNotEmpty($issuer);

        $payloads = array_merge(OpenIdClaims::build($account, $scopes), [
            'iss' => $issuer,
            'aud' => $clientId,
            'exp' => Carbon::now()->addHour()->toDateTimeImmutable(),
        ]);
        if ($nonce !== null) {
            $payloads['nonce'] = $nonce;
        }

        return Yii::$app->tokens->create($payloads);
    }

    public function createForMinecraftAccount(Account $account, string $clientToken): UnencryptedToken {
        return Yii::$app->tokens->create([
            'scope' => $this->prepareScopes([PublicScopeRepository::ACCOUNT_INFO, PublicScopeRepository::MINECRAFT_SERVER_SESSION]),
            'ely-client-token' => new EncryptedValue($clientToken),
            'sub' => $this->buildSub($account->id),
            'exp' => Carbon::now()->addDays(2)->toDateTimeImmutable(),
        ]);
    }

    /**
     * @param ScopeEntityInterface[]|string[] $scopes
     */
    private function prepareScopes(array $scopes): string {
        return implode(' ', array_map(function($scope): string {
            if ($scope instanceof ScopeEntityInterface) {
                return $scope->getIdentifier();
            }

            return $scope;
        }, $scopes));
    }

    private function buildSub(int $accountId): string {
        return self::SUB_ACCOUNT_PREFIX . $accountId;
    }

}
