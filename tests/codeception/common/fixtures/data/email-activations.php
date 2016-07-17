<?php
return [
    'freshRegistrationConfirmation' => [
        'key' => 'HABGCABHJ1234HBHVD',
        'account_id' => 3,
        'type' => \common\models\EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        'created_at' => time(),
    ],
    'oldRegistrationConfirmation' => [
        'key' => 'H23HBDCHHAG2HGHGHS',
        'account_id' => 4,
        'type' => \common\models\EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        'created_at' => time() - (new \common\models\confirmations\RegistrationConfirmation())->repeatTimeout - 10,
    ],
    'freshPasswordRecovery' => [
        'key' => 'H24HBDCHHAG2HGHGHS',
        'account_id' => 5,
        'type' => \common\models\EmailActivation::TYPE_FORGOT_PASSWORD_KEY,
        'created_at' => time(),
    ],
    'oldPasswordRecovery' => [
        'key' => 'H25HBDCHHAG2HGHGHS',
        'account_id' => 6,
        'type' => \common\models\EmailActivation::TYPE_FORGOT_PASSWORD_KEY,
        'created_at' => time() - (new \common\models\confirmations\ForgotPassword())->repeatTimeout - 10,
    ],
    'currentChangeEmailConfirmation' => [
        'key' => 'H27HBDCHHAG2HGHGHS',
        'account_id' => 7,
        'type' => \common\models\EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION,
        'created_at' => time() - 10,
    ],
    'newEmailConfirmation' => [
        'key' => 'H28HBDCHHAG2HGHGHS',
        'account_id' => 8,
        'type' => \common\models\EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION,
        '_data' => serialize(['newEmail' => 'my-new-email@ely.by']),
        'created_at' => time() - 10,
    ],
];
