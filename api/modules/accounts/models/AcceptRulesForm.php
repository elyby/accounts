<?php
namespace api\modules\accounts\models;

use yii\base\ErrorException;
use const \common\LATEST_RULES_VERSION;

class AcceptRulesForm extends AccountActionForm {

    public function performAction(): bool {
        $account = $this->getAccount();
        $account->rules_agreement_version = LATEST_RULES_VERSION;
        if (!$account->save()) {
            throw new ErrorException('Cannot set user rules version');
        }

        return true;
    }

}
