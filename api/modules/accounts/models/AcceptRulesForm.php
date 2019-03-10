<?php
namespace api\modules\accounts\models;

use api\aop\annotations\CollectModelMetrics;
use yii\base\ErrorException;
use const common\LATEST_RULES_VERSION;

class AcceptRulesForm extends AccountActionForm {

    /**
     * @CollectModelMetrics(prefix="accounts.acceptRules")
     */
    public function performAction(): bool {
        $account = $this->getAccount();
        $account->rules_agreement_version = LATEST_RULES_VERSION;
        if (!$account->save()) {
            throw new ErrorException('Cannot set user rules version');
        }

        return true;
    }

}
