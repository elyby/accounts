<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class OptionsRoute extends BasePage {

    public function get() {
        $this->route = ['options/index'];
        $this->actor->sendGET($this->getUrl());
    }

}
