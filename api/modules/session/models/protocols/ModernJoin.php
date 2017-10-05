<?php
namespace api\modules\session\models\protocols;

class ModernJoin extends BaseJoin {

    private $accessToken;
    private $selectedProfile;
    private $serverId;

    public function __construct(string $accessToken, string $selectedProfile, string $serverId) {
        $this->accessToken = trim($accessToken);
        $this->selectedProfile = trim($selectedProfile);
        $this->serverId = trim($serverId);
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function getSelectedProfile(): string {
        return $this->selectedProfile;
    }

    public function getServerId(): string {
        return $this->serverId;
    }

    public function validate(): bool {
        return !$this->isEmpty($this->accessToken) && !$this->isEmpty($this->selectedProfile) && !$this->isEmpty($this->serverId);
    }

}
