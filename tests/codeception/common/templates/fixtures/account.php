<?php
/**
 * @var $faker \Faker\Generator
 * @var $index integer
 */

$security = Yii::$app->getSecurity();

return [
    'uuid' => $faker->uuid,
    'username' => $faker->userName,
    'email' => $faker->email,
    'password_hash' => $security->generatePasswordHash('password_' . $index),
    'password_hash_strategy' => \common\models\Account::PASS_HASH_STRATEGY_YII2,
    'password_reset_token' => NULL,
    'auth_key' => $security->generateRandomString(),
    'status' => \common\models\Account::STATUS_ACTIVE,
    'created_at' => time(),
    'updated_at' => time(),
];
