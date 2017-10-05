<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\ChangePasswordForm;

class ChangePasswordAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return ChangePasswordForm::class;
    }

}
