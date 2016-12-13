<?php
return [
    'admin-test1' => [
        'access_token' => '07541285-831e-1e47-e314-b950309a6fca',
        'session_id' => 1,
        'expire_time' => time() + 3600,
    ],
    'admin-test1-expired' => [
        'access_token' => '2977ec21-3022-96f8-544db-2e1df936908',
        'session_id' => 1,
        'expire_time' => time() - 3600,
    ],
];
