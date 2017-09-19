<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class TwoFactorAuthRoute extends BasePage {

    public function credentials(int $accountId) {
        $this->setRoute($accountId);
        $this->actor->sendGET($this->getUrl());
    }

    public function enable(int $accountId, $totp = null, $password = null) {
        $this->setRoute($accountId);
        $this->actor->sendPOST($this->getUrl(), [
            'totp' => $totp,
            'password' => $password,
        ]);
    }

    public function disable(int $accountId, $totp = null, $password = null) {
        $this->setRoute($accountId);
        $this->actor->sendDELETE($this->getUrl(), [
            'totp' => $totp,
            'password' => $password,
        ]);
    }

    private function setRoute(int $accountId) {
        $this->route = "/v1/accounts/{$accountId}/two-factor-auth";
    }

}
