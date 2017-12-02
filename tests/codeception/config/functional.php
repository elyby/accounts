<?php
return [
    'components' => [
        'request' => [
            // it's not recommended to run functional tests with CSRF validation enabled
            'enableCsrfValidation' => false,
            'enableCookieValidation' => false,
            // but if you absolutely need it set cookie domain to localhost
        ],
    ],
];
