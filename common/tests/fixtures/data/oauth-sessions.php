<?php
return [
    'admin-test1' => [
        'account_id' => 1,
        'client_id' => 'test1',
        'legacy_id' => 1,
        'scopes' => null,
        'created_at' => 1479944472,
        'revoked_at' => null,
        'last_used_at' => 1479944472,
    ],
    'revoked-tlauncher' => [
        'account_id' => 1,
        'client_id' => 'tlauncher',
        'legacy_id' => null,
        'scopes' => null,
        'created_at' => Carbon\Carbon::create(2019, 8, 1, 0, 0, 0, 'Europe/Minsk')->unix(),
        'revoked_at' => Carbon\Carbon::create(2019, 8, 1, 1, 2, 0, 'Europe/Minsk')->unix(),
        'last_used_at' => Carbon\Carbon::create(2019, 8, 1, 0, 0, 0, 'Europe/Minsk')->unix(),
    ],
    'revoked-minecraft-game-launchers' => [
        'account_id' => 1,
        'client_id' => common\models\OauthClient::UNAUTHORIZED_MINECRAFT_GAME_LAUNCHER,
        'legacy_id' => null,
        'scopes' => null,
        'created_at' => Carbon\Carbon::create(2019, 8, 1, 0, 0, 0, 'Europe/Minsk')->unix(),
        'revoked_at' => Carbon\Carbon::create(2019, 8, 1, 1, 2, 0, 'Europe/Minsk')->unix(),
        'last_used_at' => Carbon\Carbon::create(2019, 8, 1, 0, 0, 0, 'Europe/Minsk')->unix(),
    ],
    'banned-account-session' => [
        'account_id' => 10,
        'client_id' => 'test1',
        'legacy_id' => 2,
        'scopes' => null,
        'created_at' => 1481421663,
        'revoked_at' => null,
        'last_used_at' => 1481421663,
    ],
    'deleted-client-session' => [
        'account_id' => 1,
        'client_id' => 'deleted-oauth-client-with-sessions',
        'legacy_id' => 3,
        'scopes' => null,
        'created_at' => 1519510065,
        'revoked_at' => null,
        'last_used_at' => 1519510065,
    ],
    'actual-deleted-client-session' => [
        'account_id' => 2,
        'client_id' => 'deleted-oauth-client-with-sessions',
        'legacy_id' => 4,
        'scopes' => null,
        'created_at' => 1519511568,
        'revoked_at' => null,
        'last_used_at' => 1519511568,
    ],
];
