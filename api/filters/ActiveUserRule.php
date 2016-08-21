<?php
namespace api\filters;

use common\models\Account;
use Yii;
use yii\filters\AccessRule;

class ActiveUserRule extends AccessRule {

    public $roles = ['@'];

    public $allow = true;

    /**
     * @inheritdoc
     */
    protected function matchCustom($action) {
        $account = $this->getIdentity();

        return $account->status === Account::STATUS_ACTIVE
            && $account->isAgreedWithActualRules();
    }

    protected function getIdentity() {
        return Yii::$app->getUser()->getIdentity();
    }

}
