<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class TwoFactorAuthRoute extends BasePage {

    public function credentials() {
        $this->route = '/two-factor-auth';
        $this->actor->sendGET($this->getUrl());
    }

}
