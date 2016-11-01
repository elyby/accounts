<?php
namespace api\models\profile;

use api\models\base\ApiForm;
use common\models\Account;
use yii\base\ErrorException;
use const \common\LATEST_RULES_VERSION;

class AcceptRulesForm extends ApiForm {

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        $this->account = $account;
        parent::__construct($config);
    }

    public function agreeWithLatestRules() : bool {
        $account = $this->getAccount();
        $account->rules_agreement_version = LATEST_RULES_VERSION;
        if (!$account->save()) {
            throw new ErrorException('Cannot set user rules version');
        }

        return true;
    }

    public function getAccount() : Account {
        return $this->account;
    }

}
