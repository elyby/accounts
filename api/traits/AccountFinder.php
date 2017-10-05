<?php
namespace api\traits;

use common\models\Account;

trait AccountFinder {

    private $account;

    public abstract function getLogin(): string;

    public function getAccount(): ?Account {
        if ($this->account === null) {
            $this->account = Account::findOne([$this->getLoginAttribute() => $this->getLogin()]);
        }

        return $this->account;
    }

    public function getLoginAttribute(): string {
        return strpos($this->getLogin(), '@') ? 'email' : 'username';
    }

}
