<?php
return [
    'admin-test1' => [
        'account_id' => 1,
        'client_id' => 'test1',
        'legacy_id' => 1,
        'scopes' => null,
        'created_at' => 1479944472,
    ],
    'banned-account-session' => [
        'account_id' => 10,
        'client_id' => 'test1',
        'legacy_id' => 2,
        'scopes' => null,
        'created_at' => 1481421663,
    ],
    'deleted-client-session' => [
        'account_id' => 1,
        'client_id' => 'deleted-oauth-client-with-sessions',
        'legacy_id' => 3,
        'scopes' => null,
        'created_at' => 1519510065,
    ],
    'actual-deleted-client-session' => [
        'account_id' => 2,
        'client_id' => 'deleted-oauth-client-with-sessions',
        'legacy_id' => 4,
        'scopes' => null,
        'created_at' => 1519511568,
    ],
];
