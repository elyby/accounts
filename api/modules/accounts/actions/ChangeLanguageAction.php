<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\ChangeLanguageForm;

class ChangeLanguageAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return ChangeLanguageForm::class;
    }

}
