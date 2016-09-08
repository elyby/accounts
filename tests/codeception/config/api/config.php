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
    'params' => [
        'authserverDomain' => 'http://authserver.ely.by',
    ],
];
