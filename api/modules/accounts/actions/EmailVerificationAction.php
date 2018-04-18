<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\AccountActionForm;
use api\modules\accounts\models\SendEmailVerificationForm;
use common\helpers\Error as E;

class EmailVerificationAction extends BaseAccountAction {

    /**
     * @param SendEmailVerificationForm|AccountActionForm $model
     * @return array
     */
    public function getFailedResultData(AccountActionForm $model): array {
        $emailError = $model->getFirstError('email');
        if ($emailError !== E::RECENTLY_SENT_MESSAGE) {
            return [];
        }

        $emailActivation = $model->getEmailActivation();

        return [
            'canRepeatIn' => $emailActivation->canRepeatIn(),
            'repeatFrequency' => $emailActivation->repeatTimeout,
        ];
    }

    protected function getFormClassName(): string {
        return SendEmailVerificationForm::class;
    }

}
