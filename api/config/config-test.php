<?php
return [
    'components' => [
        'tokens' => [
            'hmacKey' => 'tests-secret-key',
            'privateKeyPath' => codecept_data_dir('certs/private.pem'),
            'privateKeyPass' => null,
            'encryptionKey' => 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
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
        'singletons' => [
            api\components\ReCaptcha\Validator::class => function() {
                return new class(new GuzzleHttp\Client()) extends api\components\ReCaptcha\Validator {
                    protected function validateValue($value) {
                        return null;
                    }
                };
            },
            common\components\SkinsSystemApi::class => function() {
                return new class('http://chrly.ely.by') extends common\components\SkinsSystemApi {
                    public function textures(string $username): ?array {
                        return [
                            'SKIN' => [
                                'url' => 'http://localhost/skin.png',
                            ],
                        ];
                    }

                    public function profile(string $username, bool $signed = false, ?string $fallbackUuid = null): ?array {
                        if ($username === 'NotSynchronized') {
                            if ($fallbackUuid === null) {
                                return null;
                            }

                            $profile = [
                                'name' => $username,
                                'id' => $fallbackUuid,
                                'properties' => [
                                    [
                                        'name' => 'textures',
                                        'value' => base64_encode(json_encode([
                                            'timestamp' => Carbon\Carbon::now()->getPreciseTimestamp(3),
                                            'profileId' => $fallbackUuid,
                                            'profileName' => $username,
                                            'textures' => new ArrayObject(),
                                        ])),
                                    ],
                                    [
                                        'name' => 'ely',
                                        'value' => 'but why are you asking?',
                                    ],
                                ],
                            ];

                            if ($signed) {
                                $profile['properties'][0]['signature'] = 'signature';
                            }

                            return $profile;
                        }

                        $account = common\models\Account::findOne(['username' => $username]);
                        $uuid = $account ? str_replace('-', '', $account->uuid) : '00000000000000000000000000000000';

                        $profile = [
                            'name' => $username,
                            'id' => $uuid,
                            'properties' => [
                                [
                                    'name' => 'textures',
                                    'value' => base64_encode(json_encode([
                                        'timestamp' => Carbon\Carbon::now()->getPreciseTimestamp(3),
                                        'profileId' => $uuid,
                                        'profileName' => $username,
                                        'textures' => [
                                            'SKIN' => [
                                                'url' => 'http://ely.by/skin.png',
                                            ],
                                        ],
                                    ])),
                                ],
                                [
                                    'name' => 'ely',
                                    'value' => 'but why are you asking?',
                                ],
                            ],
                        ];

                        if ($signed) {
                            $profile['properties'][0]['signature'] = 'signature';
                        }

                        return $profile;
                    }

                    public function getSignatureVerificationKey(string $format = 'pem'): string {
                        return "-----BEGIN PUBLIC KEY-----\nMFwwDQYJKoZIhvcNAQEBBQADSwAwSAJBANbUpVCZkMKpfvYZ08W3lumdAaYxLBnm\nUDlzHBQH3DpYef5WCO32TDU6feIJ58A0lAywgtZ4wwi2dGHOz/1hAvcCAwEAAQ==\n-----END PUBLIC KEY-----";
                    }
                };
            },
        ],
    ],
];
