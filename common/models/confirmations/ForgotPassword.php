<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;
use yii\helpers\ArrayHelper;

class ForgotPassword extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_FORGOT_PASSWORD_KEY);
    }

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'expirationBehavior' => [
                'repeatTimeout' => 30 * 60,
                'expirationTimeout' => 1 * 60 * 60,
            ],
        ]);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_FORGOT_PASSWORD_KEY;
    }

}
