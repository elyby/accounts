<?php
namespace tests\codeception\api\_pages;

class OauthRoute extends BasePage {

    public function validate($queryParams) {
        $this->getActor()->sendGET('/oauth2/v1/validate', $queryParams);
    }

    public function complete($queryParams = [], $postParams = []) {
        $this->getActor()->sendPOST('/oauth2/v1/complete?' . http_build_query($queryParams), $postParams);
    }

    public function issueToken($postParams = []) {
        $this->getActor()->sendPOST('/oauth2/v1/token', $postParams);
    }

}
