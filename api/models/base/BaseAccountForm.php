<?php
namespace api\models\base;

use common\models\Account;

class BaseAccountForm extends ApiForm {

    /**
     * @var Account
     */
    private $account;

    public function __construct(Account $account, array $config = []) {
        parent::__construct($config);
        $this->account = $account;
    }

    public function getAccount(): Account {
        return $this->account;
    }

}
