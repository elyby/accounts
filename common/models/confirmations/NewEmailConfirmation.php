<?php
namespace common\models\confirmations;

use common\models\EmailActivation;
use yii\helpers\ArrayHelper;

/**
 * Behaviors:
 * @mixin NewEmailConfirmationBehavior
 */
class NewEmailConfirmation extends EmailActivation {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'expirationBehavior' => [
                'repeatTimeout' => 5 * 60,
            ],
            'dataBehavior' => [
                'class' => NewEmailConfirmationBehavior::class,
            ],
        ]);
    }

    public function init() {
        parent::init();
        $this->type = EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION;
    }

}
