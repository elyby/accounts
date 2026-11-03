<?php
declare(strict_types=1);

namespace common\components\OAuth2;

use api\rbac\Permissions as P;
use common\models\Account;

/**
 * @see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
 */
final class OpenIdClaims {

    /**
     * The list of the claims, which can be returned. Used in the discovery document.
     */
    public const array SUPPORTED_CLAIMS = [
        'sub',
        'name',
        'nickname',
        'preferred_username',
        'profile',
        'locale',
        'email',
        'email_verified',
    ];

    /**
     * @param non-empty-string[] $scopes
     *
     * @return array<string, string|bool>
     */
    public static function build(Account $account, array $scopes): array {
        $claims = [
            // The subject identifier must be stable and must never be reassigned (OpenID Connect Core 1.0 section 2)
            'sub' => (string)$account->id,
        ];

        // The claims sets are granted by the scopes (OpenID Connect Core 1.0 section 5.4). Since both the OpenID Connect
        // scopes and our own ones grant the same permissions, the claims are determined by the permissions
        // TODO: in future must be reimplemented with the correct RBAC resolution
        $permissions = array_map(ScopesMapper::toPermission(...), $scopes);
        if (in_array(P::OBTAIN_OWN_ACCOUNT_INFO, $permissions, true)) {
            // There is no real name of the user, so the username is used as the name
            $claims['name'] = $account->username;
            $claims['nickname'] = $account->username;
            $claims['preferred_username'] = $account->username;
            $claims['profile'] = $account->getProfileLink();
            $claims['locale'] = $account->lang;
        }

        if (in_array(P::OBTAIN_ACCOUNT_EMAIL, $permissions, true)) {
            $claims['email'] = $account->email;
            // The email is always confirmed before it's set, except the accounts with not finished registration
            $claims['email_verified'] = $account->status !== Account::STATUS_REGISTERED;
        }

        return $claims;
    }

}
