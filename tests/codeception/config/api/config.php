<?php
return [
    'components' => [
        'user' => [
            'secret' => 'tests-secret-key',
        ],
        'reCaptcha' => [
            'public' => 'public-key',
            'secret' => 'private-key',
        ],
    ],
    'modules' => [
        'authserver' => [
            'host' => 'localhost',
        ],
    ],
    'params' => [
        'authserverHost' => 'authserver.ely.by',
    ],
];
