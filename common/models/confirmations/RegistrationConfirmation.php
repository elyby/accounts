<?php
namespace common\models\confirmations;

use common\models\EmailActivation;

class RegistrationConfirmation extends EmailActivation {

    public function init() {
        parent::init();
        $this->type = EmailActivation::TYPE_REGISTRATION_EMAIL_CONFIRMATION;
    }

}
