<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class EmailConfirmRoute extends BasePage {

    public $route = ['signup/confirm'];

    public function confirm($key = '') {
        $this->actor->sendPOST($this->getUrl(), [
            'key' => $key,
        ]);
    }

}
