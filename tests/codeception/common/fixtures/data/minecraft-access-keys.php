<?php
return [
    'admin-token' => [
        'access_token' => 'e7bb6648-2183-4981-9b86-eba5e7f87b42',
        'client_token' => '6f380440-0c05-47bd-b7c6-d011f1b5308f',
        'account_id' => 1,
        'created_at' => time() - 10,
        'updated_at' => time() - 10,
    ],
    'expired-token' => [
        'access_token' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
        'client_token' => '47fb164a-2332-42c1-8bad-549e67bb210c',
        'account_id' => 1,
        'created_at' => 1472423530,
        'updated_at' => 1472423530,
    ],
    'banned-token' => [
        'access_token' => '918ecb41-616c-40ee-a7d2-0b0ef0d0d732',
        'client_token' => '6042634a-a1e2-4aed-866c-c661fe4e63e2',
        'account_id' => 10,
        'created_at' => time() - 10,
        'updated_at' => time() - 10,
    ],
];
