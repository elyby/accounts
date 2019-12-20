<?php

use Carbon\Carbon;
use common\models\EmailActivation;

return [
    'freshRegistrationConfirmation' => [
        'key' => 'HABGCABHJ1234HBHVD',
        'account_id' => 3,
        'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        'data' => null,
        'created_at' => time(),
    ],
    'oldRegistrationConfirmation' => [
        'key' => 'H23HBDCHHAG2HGHGHS',
        'account_id' => 4,
        'type' => EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        'data' => null,
        'created_at' => Carbon::now()->subMinutes(5)->subSeconds(10)->unix(),
    ],
    'freshPasswordRecovery' => [
        'key' => 'H24HBDCHHAG2HGHGHS',
        'account_id' => 5,
        'type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY,
        'data' => null,
        'created_at' => time(),
    ],
    'oldPasswordRecovery' => [
        'key' => 'H25HBDCHHAG2HGHGHS',
        'account_id' => 6,
        'type' => EmailActivation::TYPE_FORGOT_PASSWORD_KEY,
        'data' => null,
        'created_at' => Carbon::now()->subMinutes(30)->subSeconds(10)->unix(),
    ],
    'currentChangeEmailConfirmation' => [
        'key' => 'H27HBDCHHAG2HGHGHS',
        'account_id' => 7,
        'type' => EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
        'data' => null,
        'created_at' => time() - 10,
    ],
    'newEmailConfirmation' => [
        'key' => 'H28HBDCHHAG2HGHGHS',
        'account_id' => 8,
        'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        'data' => ['newEmail' => 'my-new-email@ely.by'],
        'created_at' => time() - 10,
    ],
    'deeplyExpiredConfirmation' => [
        'key' => 'H29HBDCHHAG2HGHGHS',
        'account_id' => 1,
        'type' => EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        'data' => null,
        'created_at' => 1487695872,
    ],
];
