<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class MojangApiRoute extends BasePage {

    public function usernameToUuid($username, $at = null) {
        $this->route = '/mojang/profiles/' . $username;
        $params = $at === null ? [] : ['at' => $at];
        $this->actor->sendGET($this->getUrl(), $params);
    }

    public function usernamesByUuid($uuid) {
        $this->route = "/mojang/profiles/{$uuid}/names";
        $this->actor->sendGET($this->getUrl());
    }

    public function uuidsByUsernames($uuids) {
        $this->route = '/mojang/profiles';
        $this->actor->sendPOST($this->getUrl(), $uuids);
    }

}
