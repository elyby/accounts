<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Carbon\Carbon;
use common\models\Account;
use common\models\AccountSession;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Yii;

class TokensFactory {

    public const SUB_ACCOUNT_PREFIX = 'ely|';
    public const AUD_CLIENT_PREFIX = 'client|';

    public static function createForAccount(Account $account, AccountSession $session = null): Token {
        $payloads = [
            'ely-scopes' => 'accounts_web_user',
            'sub' => self::buildSub($account->id),
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

    public static function createForOAuthClient(AccessTokenEntityInterface $accessToken): Token {
        $payloads = [
            'aud' => self::buildAud($accessToken->getClient()->getIdentifier()),
            'ely-scopes' => implode(',', array_map(static function(ScopeEntityInterface $scope): string {
                return $scope->getIdentifier();
            }, $accessToken->getScopes())),
            'exp' => $accessToken->getExpiryDateTime()->getTimestamp(),
        ];
        if ($accessToken->getUserIdentifier() !== null) {
            $payloads['sub'] = self::buildSub($accessToken->getUserIdentifier());
        }

        return Yii::$app->tokens->create($payloads);
    }

    private static function buildSub(int $accountId): string {
        return self::SUB_ACCOUNT_PREFIX . $accountId;
    }

    private static function buildAud(string $clientId): string {
        return self::AUD_CLIENT_PREFIX . $clientId;
    }

}
