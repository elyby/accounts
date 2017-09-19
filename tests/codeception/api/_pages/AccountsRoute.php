<?php
namespace tests\codeception\api\_pages;

use yii\codeception\BasePage;

/**
 * @property \tests\codeception\api\FunctionalTester $actor
 */
class AccountsRoute extends BasePage {

    public function get(int $accountId) {
        $this->route = "/v1/accounts/{$accountId}";
        $this->actor->sendGET($this->getUrl());
    }

    public function changePassword(int $accountId, $currentPassword = null, $newPassword = null, $newRePassword = null) {
        $this->route = "/v1/accounts/{$accountId}/password";
        $this->actor->sendPOST($this->getUrl(), [
            'password' => $currentPassword,
            'newPassword' => $newPassword,
            'newRePassword' => $newRePassword,
        ]);
    }

    public function changeUsername(int $accountId, $currentPassword = null, $newUsername = null) {
        $this->route = "/v1/accounts/{$accountId}/username";
        $this->actor->sendPOST($this->getUrl(), [
            'password' => $currentPassword,
            'username' => $newUsername,
        ]);
    }

    public function changeEmailInitialize(int $accountId, $password = '') {
        $this->route = "/v1/accounts/{$accountId}/email-verification";
        $this->actor->sendPOST($this->getUrl(), [
            'password' => $password,
        ]);
    }

    public function changeEmailSubmitNewEmail(int $accountId, $key = null, $email = null) {
        $this->route = "/v1/accounts/{$accountId}/new-email-verification";
        $this->actor->sendPOST($this->getUrl(), [
            'key' => $key,
            'email' => $email,
        ]);
    }

    public function changeEmail(int $accountId, $key = null) {
        $this->route = "/v1/accounts/{$accountId}/email";
        $this->actor->sendPOST($this->getUrl(), [
            'key' => $key,
        ]);
    }

    public function changeLanguage(int $accountId, $lang = null) {
        $this->route = "/v1/accounts/{$accountId}/language";
        $this->actor->sendPOST($this->getUrl(), [
            'lang' => $lang,
        ]);
    }

    public function acceptRules(int $accountId) {
        $this->route = "/v1/accounts/{$accountId}/rules";
        $this->actor->sendPOST($this->getUrl());
    }

    public function ban(int $accountId) {
        $this->route = "/v1/accounts/{$accountId}/ban";
        $this->actor->sendPOST($this->getUrl());
    }

    public function pardon($accountId) {
        $this->route = "/v1/accounts/{$accountId}/ban";
        $this->actor->sendDELETE($this->getUrl());
    }

}
