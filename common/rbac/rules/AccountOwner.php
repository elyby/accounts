<?php
namespace common\rbac\rules;

use common\models\Account;
use Yii;
use yii\rbac\Rule;

class AccountOwner extends Rule {

    public $name = 'account_owner';

    /**
     * In our application the permissions are given not to users but to tokens,
     * so we receive $accessToken here and extract all the assigned scopes from it.
     *
     * @param string|int     $accessToken
     * @param \yii\rbac\Item $item
     * @param array          $params the "accountId" parameter must be passed as the id of the account
     *                               to which the request is made
     *                               the "optionalRules" parameter allows you to disable the mandatory acceptance
     *                               of the latest version of the rules
     *
     * @return bool a value indicating whether the rule permits the auth item it is associated with.
     */
    public function execute($accessToken, $item, $params): bool {
        $accountId = $params['accountId'] ?? null;
        if ($accountId === null) {
            return false;
        }

        $identity = Yii::$app->user->findIdentityByAccessToken($accessToken);
        if ($identity === null) {
            return false;
        }

        $account = $identity->getAccount();
        if ($account === null) {
            return false;
        }

        if ($account->id !== (int)$accountId) {
            return false;
        }

        if ($account->status !== Account::STATUS_ACTIVE) {
            return false;
        }

        $actualRulesOptional = $params['optionalRules'] ?? false;
        if (!$actualRulesOptional && !$account->isAgreedWithActualRules()) {
            return false;
        }

        return true;
    }

}
