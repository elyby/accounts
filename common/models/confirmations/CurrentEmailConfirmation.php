<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;
use DateInterval;

class CurrentEmailConfirmation extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION;
    }

    protected function getResendTimeout(): ?DateInterval {
        return new DateInterval('PT6H');
    }

    protected function getExpireDuration(): ?DateInterval {
        return new DateInterval('PT1H');
    }

}
