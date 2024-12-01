<?php
declare(strict_types=1);

namespace api\models\base;

use common\models\Account;

class BaseAccountForm extends ApiForm {

    public function __construct(
        private Account $account,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function getAccount(): Account {
        return $this->account;
    }

}
