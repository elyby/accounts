<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class InternalRoute extends BasePage {

    public function ban($accountId) {
        $this->route = '/internal/accounts/' . $accountId . '/ban';
        $this->actor->sendPOST($this->getUrl());
    }

}
