<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\EnableTwoFactorAuthForm;

class EnableTwoFactorAuthAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return EnableTwoFactorAuthForm::class;
    }

}
