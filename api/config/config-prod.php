<?php
return [
    'components' => [
        'reCaptcha' => [
            'public' => getenv('RECAPTCHA_PUBLIC'),
            'secret' => getenv('RECAPTCHA_SECRET'),
        ],
    ],
];
