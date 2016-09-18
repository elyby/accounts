<?php
return [
    'components' => [
        'request' => [
            // it's not recommended to run functional tests with CSRF validation enabled
            // TODO: у нас вроде и без того нет проверки csrf
            'enableCsrfValidation' => false,
            'enableCookieValidation' => false,
            // but if you absolutely need it set cookie domain to localhost
        ],
    ],
];
