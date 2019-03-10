<?php
namespace api\tests\_pages;

class OauthRoute extends BasePage {

    public function validate(array $queryParams): void {
        $this->getActor()->sendGET('/api/oauth2/v1/validate', $queryParams);
    }

    public function complete(array $queryParams = [], array $postParams = []): void {
        $this->getActor()->sendPOST('/api/oauth2/v1/complete?' . http_build_query($queryParams), $postParams);
    }

    public function issueToken(array $postParams = []): void {
        $this->getActor()->sendPOST('/api/oauth2/v1/token', $postParams);
    }

    public function createClient(string $type, array $postParams): void {
        $this->getActor()->sendPOST('/api/v1/oauth2/' . $type, $postParams);
    }

    public function updateClient(string $clientId, array $params): void {
        $this->getActor()->sendPUT('/api/v1/oauth2/' . $clientId, $params);
    }

    public function deleteClient(string $clientId): void {
        $this->getActor()->sendDELETE('/api/v1/oauth2/' . $clientId);
    }

    public function resetClient(string $clientId, bool $regenerateSecret = false): void {
        $this->getActor()->sendPOST("/api/v1/oauth2/{$clientId}/reset" . ($regenerateSecret ? '?regenerateSecret' : ''));
    }

    public function getClient(string $clientId): void {
        $this->getActor()->sendGET("/api/v1/oauth2/{$clientId}");
    }

    public function getPerAccount(int $accountId): void {
        $this->getActor()->sendGET("/api/v1/accounts/{$accountId}/oauth2/clients");
    }

}
