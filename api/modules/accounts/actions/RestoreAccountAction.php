<?php
declare(strict_types=1);

namespace api\modules\accounts\actions;

use api\modules\accounts\models\RestoreAccountForm;

final class RestoreAccountAction extends BaseAccountAction {

    protected function getFormClassName(): string {
        return RestoreAccountForm::class;
    }

}
