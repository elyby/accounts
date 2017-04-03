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

    public function pardon($accountId) {
        $this->route = '/internal/accounts/' . $accountId . '/ban';
        $this->actor->sendDELETE($this->getUrl());
    }

    public function info(string $param, string $value) {
        $this->route = '/internal/accounts/info';
        $this->actor->sendGET($this->getUrl(), [$param => $value]);
    }

}
