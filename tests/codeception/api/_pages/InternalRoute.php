<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class InternalRoute extends BasePage {

    public function info(string $param, string $value) {
        $this->route = '/internal/accounts/info';
        $this->actor->sendGET($this->getUrl(), [$param => $value]);
    }

}
