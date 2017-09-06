<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class TwoFactorAuthRoute extends BasePage {

    public $route = '/two-factor-auth';

    public function credentials() {
        $this->actor->sendGET($this->getUrl());
    }

    public function enable($totp = null, $password = null) {
        $this->actor->sendPOST($this->getUrl(), [
            'totp' => $totp,
            'password' => $password,
        ]);
    }

    public function disable($totp = null, $password = null) {
        $this->actor->sendDELETE($this->getUrl(), [
            'totp' => $totp,
            'password' => $password,
        ]);
    }

}
