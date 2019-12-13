<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;
use yii\helpers\ArrayHelper;

/**
 * Behaviors:
 * @mixin NewEmailConfirmationBehavior
 */
class NewEmailConfirmation extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION);
    }

    public function behaviors(): array {
        return ArrayHelper::merge(parent::behaviors(), [
            'expirationBehavior' => [
                'repeatTimeout' => 5 * 60,
            ],
            'dataBehavior' => [
                'class' => NewEmailConfirmationBehavior::class,
            ],
        ]);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION;
    }

}
