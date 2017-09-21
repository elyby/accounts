<?php
namespace common\rbac\rules;

use common\models\Account;
use Yii;
use yii\base\InvalidParamException;
use yii\rbac\Rule;

class AccountOwner extends Rule {

    public $name = 'account_owner';

    /**
     * В нашем приложении права выдаются не пользователям, а токенам, так что ожидаем
     * здесь $accessToken, по которому дальше восстанавливаем аккаунт, если это возможно.
     *
     * @param string|int     $accessToken
     * @param \yii\rbac\Item $item
     * @param array          $params параметр accountId нужно передать обязательно как id аккаунта,
     *                               к которому выполняется запрос
     *                               параметр optionalRules позволяет отключить обязательность
     *                               принятия последней версии правил
     *
     * @return bool a value indicating whether the rule permits the auth item it is associated with.
     */
    public function execute($accessToken, $item, $params): bool {
        $accountId = $params['accountId'] ?? null;
        if ($accountId === null) {
            throw new InvalidParamException('params don\'t contain required key: accountId');
        }

        $identity = Yii::$app->user->findIdentityByAccessToken($accessToken);
        /** @noinspection NullPointerExceptionInspection это исключено, т.к. уже сработал authManager */
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
