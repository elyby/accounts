<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\DisableTwoFactorAuthForm;

class DisableTwoFactorAuthAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return DisableTwoFactorAuthForm::class;
    }

}
