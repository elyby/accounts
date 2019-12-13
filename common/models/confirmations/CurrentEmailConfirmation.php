<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;
use yii\helpers\ArrayHelper;

class CurrentEmailConfirmation extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION);
    }

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'expirationBehavior' => [
                'repeatTimeout' => 6 * 60 * 60, // 6h
                'expirationTimeout' => 1 * 60 * 60, // 1h
            ],
        ]);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION;
    }

}
