<?php
namespace api\modules\accounts\models;

use common\models\Account;
use OTPHP\TOTP;

trait TotpHelper {

    protected function getTotp(): TOTP {
        $account = $this->getAccount();
        $totp = TOTP::create($account->otp_secret);
        $totp->setLabel($account->email);
        $totp->setIssuer('Ely.by');

        return $totp;
    }

    abstract public function getAccount(): Account;

}
