<?php
use api\components\ReCaptcha\Validator;
use GuzzleHttp\Client;

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
    'container' => [
        'definitions' => [
            Validator::class => function() {
                return new class(new Client()) extends Validator {
                    protected function validateValue($value) {
                        return null;
                    }
                };
            },
        ],
    ],
];
