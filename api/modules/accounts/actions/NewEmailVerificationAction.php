<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\SendNewEmailVerificationForm;

class NewEmailVerificationAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return SendNewEmailVerificationForm::class;
    }

}
