<?php
namespace api\tests\_pages;

class AccountsRoute extends BasePage {

    public function get(int $accountId): void {
        $this->getActor()->sendGET("/api/v1/accounts/{$accountId}");
    }

    public function changePassword(int $accountId, $currentPassword = null, $newPassword = null, $newRePassword = null): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/password", [
            'password' => $currentPassword,
            'newPassword' => $newPassword,
            'newRePassword' => $newRePassword,
        ]);
    }

    public function changeUsername(int $accountId, $currentPassword = null, $newUsername = null): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/username", [
            'password' => $currentPassword,
            'username' => $newUsername,
        ]);
    }

    public function changeEmailInitialize(int $accountId, $password = ''): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/email-verification", [
            'password' => $password,
        ]);
    }

    public function changeEmailSubmitNewEmail(int $accountId, $key = null, $email = null): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/new-email-verification", [
            'key' => $key,
            'email' => $email,
        ]);
    }

    public function changeEmail(int $accountId, $key = null): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/email", [
            'key' => $key,
        ]);
    }

    public function changeLanguage(int $accountId, $lang = null): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/language", [
            'lang' => $lang,
        ]);
    }

    public function acceptRules(int $accountId): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/rules");
    }

    public function getTwoFactorAuthCredentials(int $accountId): void {
        $this->getActor()->sendGET("/api/v1/accounts/{$accountId}/two-factor-auth");
    }

    public function enableTwoFactorAuth(int $accountId, $totp = null, $password = null): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/two-factor-auth", [
            'totp' => $totp,
            'password' => $password,
        ]);
    }

    public function disableTwoFactorAuth(int $accountId, $totp = null, $password = null): void {
        $this->getActor()->sendDELETE("/api/v1/accounts/{$accountId}/two-factor-auth", [
            'totp' => $totp,
            'password' => $password,
        ]);
    }

    public function ban(int $accountId): void {
        $this->getActor()->sendPOST("/api/v1/accounts/{$accountId}/ban");
    }

    public function pardon(int $accountId): void {
        $this->getActor()->sendDELETE("/api/v1/accounts/{$accountId}/ban");
    }

}
