<?php
declare(strict_types=1);

namespace api\components\Tokens;

use Carbon\Carbon;
use common\models\Account;
use common\models\AccountSession;
use Lcobucci\JWT\Token;
use Yii;

class TokensFactory {

    public const SUB_ACCOUNT_PREFIX = 'ely|';

    public static function createForAccount(Account $account, AccountSession $session = null): Token {
        $payloads = [
            'ely-scopes' => 'accounts_web_user',
            'sub' => self::SUB_ACCOUNT_PREFIX . $account->id,
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

}
