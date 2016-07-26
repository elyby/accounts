<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class OauthRoute extends BasePage {

    public function validate($queryParams) {
        $this->route = '/oauth2/v1/validate';
        $this->actor->sendGET($this->getUrl($queryParams));
    }

    public function complete($queryParams = [], $postParams = []) {
        $this->route = '/oauth2/v1/complete';
        $this->actor->sendPOST($this->getUrl($queryParams), $postParams);
    }

    public function issueToken($postParams = []) {
        $this->route = '/oauth2/v1/token';
        $this->actor->sendPOST($this->getUrl(), $postParams);
    }

}
