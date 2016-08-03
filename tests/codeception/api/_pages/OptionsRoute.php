<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class OptionsRoute extends BasePage {

    public function recaptchaPublicKey() {
        $this->route = ['options/recaptcha-public-key'];
        $this->actor->sendGET($this->getUrl());
    }

}
