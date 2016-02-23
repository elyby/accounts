<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class UsersRoute extends BasePage {

    public function current() {
        $this->route = ['users/current'];
        $this->actor->sendGET($this->getUrl());
    }

}
