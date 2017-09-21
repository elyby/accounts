<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\BanAccountForm;

class BanAccountAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return BanAccountForm::class;
    }

}
