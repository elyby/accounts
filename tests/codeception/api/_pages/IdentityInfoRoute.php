<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class IdentityInfoRoute extends BasePage {

    public function info() {
        $this->route = ['identity-info/index'];
        $this->actor->sendGET($this->getUrl());
    }

}
