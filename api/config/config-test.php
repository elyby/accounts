<?php
return [
    'components' => [
        'tokens' => [
            'hmacKey' => 'tests-secret-key',
            'privateKeyPath' => codecept_data_dir('certs/private.pem'),
            'privateKeyPass' => null,
            'publicKeyPath' => codecept_data_dir('certs/public.pem'),
        ],
        'reCaptcha' => [
            'public' => 'public-key',
            'secret' => 'private-key',
        ],
    ],
    'params' => [
        'authserverHost' => 'localhost',
    ],
    'container' => [
        'definitions' => [
            api\components\ReCaptcha\Validator::class => function() {
                return new class(new GuzzleHttp\Client()) extends api\components\ReCaptcha\Validator {
                    protected function validateValue($value) {
                        return null;
                    }
                };
            },
            common\components\SkinSystem\Api::class => function() {
                return new class extends common\components\SkinSystem\Api {
                    public function textures(string $username): ?array {
                        return [
                            'SKIN' => [
                                'url' => 'http://localhost/skin.png',
                            ],
                        ];
                    }
                };
            },
        ],
    ],
];
