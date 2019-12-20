<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;
use DateInterval;

class ForgotPassword extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_FORGOT_PASSWORD_KEY);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_FORGOT_PASSWORD_KEY;
    }

    protected function getResendTimeout(): ?DateInterval {
        return new DateInterval('PT30M');
    }

    protected function getExpireDuration(): ?DateInterval {
        return new DateInterval('PT1H');
    }

}
