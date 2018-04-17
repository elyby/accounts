<?php
namespace api\modules\accounts\actions;

use api\modules\accounts\models\AccountActionForm;
use api\modules\accounts\models\ChangeEmailForm;

class ChangeEmailAction extends BaseAccountAction {

    /**
     * @param ChangeEmailForm|AccountActionForm $model
     * @return array
     */
    public function getSuccessResultData(AccountActionForm $model): array {
        return [
            'email' => $model->getAccount()->email,
        ];
    }

    protected function getFormClassName(): string {
        return ChangeEmailForm::class;
    }

}
