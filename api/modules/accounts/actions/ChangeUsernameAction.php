<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\ChangeUsernameForm;

class ChangeUsernameAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return ChangeUsernameForm::class;
    }

}
