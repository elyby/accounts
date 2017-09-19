<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\AcceptRulesForm;

class AcceptRulesAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return AcceptRulesForm::class;
    }

}
