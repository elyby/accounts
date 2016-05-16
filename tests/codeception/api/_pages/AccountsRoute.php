<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class AccountsRoute extends BasePage {

    public function current() {
        $this->route = ['accounts/current'];
        $this->actor->sendGET($this->getUrl());
    }

    public function changePassword($currentPassword = null, $newPassword = null, $newRePassword = null) {
        $this->route = ['accounts/change-password'];
        $this->actor->sendPOST($this->getUrl(), [
            'password' => $currentPassword,
            'newPassword' => $newPassword,
            'newRePassword' => $newRePassword,
        ]);
    }

    public function changeUsername($currentPassword = null, $newUsername = null) {
        $this->route = ['accounts/change-username'];
        $this->actor->sendPOST($this->getUrl(), [
            'password' => $currentPassword,
            'username' => $newUsername,
        ]);
    }

    public function changeEmailInitialize() {
        $this->route = ['accounts/change-email-initialize'];
        $this->actor->sendPOST($this->getUrl());
    }

}
