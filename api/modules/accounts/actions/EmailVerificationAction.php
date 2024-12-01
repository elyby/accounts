<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\AccountActionForm;
use api\modules\accounts\models\SendEmailVerificationForm;
use common\helpers\Error as E;

class EmailVerificationAction extends BaseAccountAction {

    /**
     * @param SendEmailVerificationForm $model
     *
     * @return array{
     *     canRepeatIn?: int,
     * }
     */
    public function getFailedResultData(AccountActionForm $model): array {
        $emailError = $model->getFirstError('email');
        if ($emailError !== E::RECENTLY_SENT_MESSAGE) {
            return [];
        }

        /** @var \common\models\EmailActivation $emailActivation */
        $emailActivation = $model->getEmailActivation();

        return [
            'canRepeatIn' => $emailActivation->canResendAt()->getTimestamp(),
        ];
    }

    protected function getFormClassName(): string {
        return SendEmailVerificationForm::class;
    }

}
