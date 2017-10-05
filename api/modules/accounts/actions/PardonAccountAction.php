<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\PardonAccountForm;

class PardonAccountAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return PardonAccountForm::class;
    }

}
