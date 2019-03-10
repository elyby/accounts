<?php
return [
    'webhook-with-secret' => [
        'id' => 1,
        'url' => 'http://localhost:80/webhooks/ely',
        'secret' => 'my-secret',
        'created_at' => 1531054333,
    ],
    'webhook-without-secret' => [
        'id' => 2,
        'url' => 'http://localhost:81/webhooks/ely',
        'secret' => null,
        'created_at' => 1531054837,
    ],
    'webhook-without-events' => [
        'id' => 3,
        'url' => 'http://localhost:82/webhooks/ely',
        'secret' => null,
        'created_at' => 1531054990,
    ],
];
