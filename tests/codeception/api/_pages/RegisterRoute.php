<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class RegisterRoute extends BasePage {

    public $route = ['signup/register'];

    public function send(array $registrationData) {
        $this->actor->sendPOST($this->getUrl(), $registrationData);
    }

}
