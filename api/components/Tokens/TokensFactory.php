<?php
declare(strict_types=1);

namespace api\components\Tokens;

use api\rbac\Permissions as P;
use api\rbac\Roles as R;
use Carbon\Carbon;
use common\models\Account;
use common\models\AccountSession;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Yii;
use yii\base\Component;

class TokensFactory extends Component {

    public const SUB_ACCOUNT_PREFIX = 'ely|';
    public const AUD_CLIENT_PREFIX = 'client|';

    public function createForWebAccount(Account $account, AccountSession $session = null): Token {
        $payloads = [
            'ely-scopes' => R::ACCOUNTS_WEB_USER,
            'sub' => $this->buildSub($account->id),
        ];
        if ($session === null) {
            // If we don't remember a session, the token should live longer
            // so that the session doesn't end while working with the account
            $payloads['exp'] = Carbon::now()->addDays(7)->getTimestamp();
        } else {
            $payloads['jti'] = $session->id;
        }

        return Yii::$app->tokens->create($payloads);
    }

    public function createForOAuthClient(AccessTokenEntityInterface $accessToken): Token {
        $payloads = [
            'aud' => $this->buildAud($accessToken->getClient()->getIdentifier()),
            'ely-scopes' => $this->joinScopes(array_map(static function(ScopeEntityInterface $scope): string {
                return $scope->getIdentifier();
            }, $accessToken->getScopes())),
            'exp' => $accessToken->getExpiryDateTime()->getTimestamp(),
        ];
        if ($accessToken->getUserIdentifier() !== null) {
            $payloads['sub'] = $this->buildSub($accessToken->getUserIdentifier());
        }

        return Yii::$app->tokens->create($payloads);
    }

    public function createForMinecraftAccount(Account $account, string $clientToken): Token {
        return Yii::$app->tokens->create([
            'ely-scopes' => $this->joinScopes([P::MINECRAFT_SERVER_SESSION]),
            'ely-client-token' => new EncryptedValue($clientToken),
            'sub' => $this->buildSub($account->id),
            'exp' => Carbon::now()->addDays(2)->getTimestamp(),
        ]);
    }

    private function joinScopes(array $scopes): string {
        return implode(',', $scopes);
    }

    private function buildSub(int $accountId): string {
        return self::SUB_ACCOUNT_PREFIX . $accountId;
    }

    private function buildAud(string $clientId): string {
        return self::AUD_CLIENT_PREFIX . $clientId;
    }

}
