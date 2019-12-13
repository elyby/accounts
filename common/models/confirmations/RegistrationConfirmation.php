<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;

class RegistrationConfirmation extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION;
    }

}
