<?php
return [
    'admin-test1' => [
        'id' => 1,
        'owner_type' => 'user',
        'owner_id' => 1,
        'client_id' => 'test1',
        'client_redirect_uri' => 'http://test1.net/oauth',
        'created_at' => 1479944472,
    ],
    'banned-account-session' => [
        'id' => 2,
        'owner_type' => 'user',
        'owner_id' => 10,
        'client_id' => 'test1',
        'client_redirect_uri' => 'http://test1.net/oauth',
        'created_at' => 1481421663,
    ],
    'deleted-client-session' => [
        'id' => 3,
        'owner_type' => 'user',
        'owner_id' => 1,
        'client_id' => 'deleted-oauth-client-with-sessions',
        'client_redirect_uri' => 'http://not-exists-site.com/oauth/ely',
        'created_at' => 1519510065,
    ],
    'actual-deleted-client-session' => [
        'id' => 4,
        'owner_type' => 'user',
        'owner_id' => 2,
        'client_id' => 'deleted-oauth-client-with-sessions',
        'client_redirect_uri' => 'http://not-exists-site.com/oauth/ely',
        'created_at' => 1519511568,
    ],
];
