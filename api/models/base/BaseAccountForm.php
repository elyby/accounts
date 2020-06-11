<?php
declare(strict_types=1);

namespace api\models\base;

use common\models\Account;

class BaseAccountForm extends ApiForm {

    private Account $account;

    public function __construct(Account $account, array $config = []) {
        parent::__construct($config);
        $this->account = $account;
    }

    public function getAccount(): Account {
        return $this->account;
    }

}
