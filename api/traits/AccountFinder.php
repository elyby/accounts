<?php
namespace api\traits;

use common\models\Account;

trait AccountFinder {

    private $account;

    public abstract function getLogin();

    /**
     * @return Account|null
     */
    public function getAccount() {
        if ($this->account === null) {
            $className = $this->getAccountClassName();
            $this->account = $className::findOne([$this->getLoginAttribute() => $this->getLogin()]);
        }

        return $this->account;
    }

    public function getLoginAttribute() {
        return strpos($this->getLogin(), '@') ? 'email' : 'username';
    }

    /**
     * @return Account|string
     */
    protected function getAccountClassName() {
        return Account::class;
    }

}
