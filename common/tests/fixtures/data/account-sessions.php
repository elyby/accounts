<?php
return [
    'admin' => [
        'id' => 1,
        'account_id' => 1,
        'refresh_token' => 'SOutIr6Seeaii3uqMVy3Wan8sKFVFrNz',
        'last_used_ip' => ip2long('127.0.0.1'),
        'created_at' => time(),
        'last_refreshed_at' => time(),
    ],
    'admin2' => [
        'id' => 2,
        'account_id' => 1,
        'refresh_token' => 'RI5CdxTama2ZijwYw03rJAq84M2JzPM3gDeIDGI8',
        'last_used_ip' => ip2long('136.243.88.97'),
        'created_at' => time(),
        'last_refreshed_at' => time(),
    ],
    'banned-user-session' => [
        'id' => 3,
        'account_id' => 10,
        'refresh_token' => 'Af7fIuV6eL61tRUHn40yhmDRXN1OQxKR',
        'last_used_ip' => ip2long('182.123.234.123'),
        'created_at' => time(),
        'last_refreshed_at' => time(),
    ],
    'very-expired-session' => [
        'id' => 4,
        'account_id' => 1,
        'refresh_token' => 'dkzIbUdtVLU3LOotSofUU0BQTvClMqmiIGwVKz2VHXOENifj',
        'last_used_ip' => ip2long('127.0.0.1'),
        'created_at' => 1477414260,
        'last_refreshed_at' => 1480092660,
    ],
    'not-refreshed-session' => [
        'id' => 5,
        'account_id' => 1,
        'refresh_token' => 'NM9g7fZyn1o3F87BkDtRCQaHLuudDxyuup3ttyDRIjmwPPJx',
        'last_used_ip' => ip2long('127.0.0.1'),
        'created_at' => time() - 1814400, // 3 weeks
        'last_refreshed_at' => time() - 1814400, // 3 weeks
    ],
];
