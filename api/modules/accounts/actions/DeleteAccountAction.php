<?php
declare(strict_types=1);

namespace api\modules\accounts\actions;

use api\modules\accounts\models\DeleteAccountForm;

final class DeleteAccountAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return DeleteAccountForm::class;
    }

}
