<?php
declare(strict_types=1);

namespace common\models\confirmations;

use common\models\EmailActivation;
use common\models\EmailActivationQuery;
use yii\helpers\ArrayHelper;

class NewEmailConfirmation extends EmailActivation {

    public static function find(): EmailActivationQuery {
        return parent::find()->withType(EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION);
    }

    public function init(): void {
        parent::init();
        $this->type = EmailActivation::TYPE_NEW_EMAIL_CONFIRMATION;
    }

    public function getNewEmail(): string {
        return $this->data['newEmail'];
    }

    public function setNewEmail(string $newEmail): void {
        $this->data = ArrayHelper::merge($this->data ?? [], [
            'newEmail' => $newEmail,
        ]);
    }

}
