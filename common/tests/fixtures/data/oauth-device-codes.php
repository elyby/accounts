<?php
declare(strict_types=1);

return [
    'pending-code' => [
        'device_code' => 'nKuYFfwckZywqU8iUKv3ek4VtiMiMCkiC0YTZFPbWycSxdRpHiYP2wnv3S0KHBgYky8fRDqfhhCqzke7',
        'user_code' => 'AAAABBBB',
        'client_id' => 'ely',
        'scopes' => ['minecraft_server_session', 'account_info'],
        'account_id' => null,
        'is_approved' => null,
        'last_polled_at' => null,
        'expires_at' => time() + 1800,
    ],
    'expired-code' => [
        'device_code' => 'ZFPbWycSxdRpHiYP2wnv3S0KHBgYky8fRDqfhhCqzke7nKuYFfwckZywqU8iUKv3ek4VtiMiMCkiC0YT',
        'user_code' => 'EXPIRED',
        'client_id' => 'ely',
        'scopes' => ['minecraft_server_session', 'account_info'],
        'account_id' => null,
        'is_approved' => null,
        'last_polled_at' => time() - 1200,
        'expires_at' => time() - 1800,
    ],
    'completed-code' => [
        'device_code' => 'xdRpHiYP2wnv3S0KHBgYky8fRDqfhhCqzke7nKuYFfwckZywqU8iUKv3ek4VtiMiMCkiC0YTZFPbWycS',
        'user_code' => 'COMPLETED',
        'client_id' => 'ely',
        'scopes' => ['minecraft_server_session', 'account_info'],
        'account_id' => 1,
        'is_approved' => true,
        'last_polled_at' => time(),
        'expires_at' => time() + 1800,
    ],
];
