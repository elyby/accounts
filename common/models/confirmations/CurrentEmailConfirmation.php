<?php
namespace common\models\confirmations;

use common\models\EmailActivation;
use yii\helpers\ArrayHelper;

class CurrentEmailConfirmation extends EmailActivation {

    public function behaviors() {
        return ArrayHelper::merge(parent::behaviors(), [
            'expirationBehavior' => [
                'repeatTimeout' => 6 * 60 * 60, // 6h
                'expirationTimeout' => 1 * 60 * 60, // 1h
            ],
        ]);
    }

    public function init() {
        parent::init();
        $this->type = EmailActivation::TYPE_CURRENT_EMAIL_CONFIRMATION;
    }

}
