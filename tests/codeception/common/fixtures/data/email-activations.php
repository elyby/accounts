<?php
return [
    [
        'key' => 'HABGCABHJ1234HBHVD',
        'account_id' => 3,
        'type' => \common\models\EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        'created_at' => time(),
    ],
    [
        'key' => 'H23HBDCHHAG2HGHGHS',
        'account_id' => 4,
        'type' => \common\models\EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION,
        'created_at' => time() - \api\models\RepeatAccountActivationForm::REPEAT_FREQUENCY - 10,
    ],
];
