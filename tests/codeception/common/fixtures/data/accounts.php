<?php
return [
    'admin' => [
        'id' => 1,
        'uuid' => 'df936908-b2e1-544d-96f8-2977ec213022',
        'username' => 'Admin',
        'email' => 'admin@ely.by',
        'password_hash' => '$2y$13$CXT0Rkle1EMJ/c1l5bylL.EylfmQ39O5JlHJVFpNn618OUS1HwaIi', # password_0
        'password_hash_strategy' => \common\models\Account::PASS_HASH_STRATEGY_YII2,
        'password_reset_token' => null,
        'status' => \common\models\Account::STATUS_ACTIVE,
        'created_at' => 1451775316,
        'updated_at' => 1451775316,
        'password_changed_at' => 1451775316,
    ],
    'user-with-old-password-type' => [
        'id' => 2,
        'uuid' => 'bdc239f0-8a22-518d-8b93-f02d4827c3eb',
        'username' => 'AccWithOldPassword',
        'email' => 'erickskrauch123@yandex.ru',
        'password_hash' => '133c00c463cbd3e491c28cb653ce4718', # 12345678
        'password_hash_strategy' => \common\models\Account::PASS_HASH_STRATEGY_OLD_ELY,
        'password_reset_token' => null,
        'status' => \common\models\Account::STATUS_ACTIVE,
        'created_at' => 1385225069,
        'updated_at' => 1385225069,
        'password_changed_at' => 1385225069,
    ],
    'not-activated-account' => [
        'id' => 3,
        'uuid' => '86c6fedb-bffc-37a5-8c0f-62e8fa9a2af7',
        'username' => 'howe.garnett',
        'email' => 'achristiansen@gmail.com',
        'password_hash' => '$2y$13$2rYkap5T6jG8z/mMK8a3Ou6aZxJcmAaTha6FEuujvHEmybSHRzW5e', # password_0
        'password_hash_strategy' => \common\models\Account::PASS_HASH_STRATEGY_YII2,
        'password_reset_token' => null,
        'status' => \common\models\Account::STATUS_REGISTERED,
        'created_at' => 1453146616,
        'updated_at' => 1453146616,
        'password_changed_at' => 1453146616,
    ],
    'not-activated-account-with-expired-message' => [
        'id' => 4,
        'uuid' => '58a7bfdc-ad0f-44c3-9197-759cb9220895',
        'username' => 'Jon',
        'email' => 'jon@ely.by',
        'password_hash' => '$2y$13$2rYkap5T6jG8z/mMK8a3Ou6aZxJcmAaTha6FEuujvHEmybSHRzW5e', # password_0
        'password_hash_strategy' => \common\models\Account::PASS_HASH_STRATEGY_YII2,
        'password_reset_token' => null,
        'auth_key' => '45DsaEQ7U8lU9umIyCWk5iCnpdPvZ8Up',
        'status' => \common\models\Account::STATUS_REGISTERED,
        'created_at' => 1457890086,
        'updated_at' => 1457890086,
    ],
];
