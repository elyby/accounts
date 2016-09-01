<?php
namespace api\modules\authserver\models;

use common\models\MinecraftAccessKey;

class AuthenticateData {

    /**
     * @var MinecraftAccessKey
     */
    private $minecraftAccessKey;

    public function __construct(MinecraftAccessKey $minecraftAccessKey) {
        $this->minecraftAccessKey = $minecraftAccessKey;
    }

    public function getMinecraftAccessKey() : MinecraftAccessKey {
        return $this->minecraftAccessKey;
    }

    public function getResponseData(bool $includeAvailableProfiles = false) : array {
        $accessKey = $this->minecraftAccessKey;
        $account = $accessKey->account;

        $result = [
            'accessToken' => $accessKey->access_token,
            'clientToken' => $accessKey->client_token,
            'selectedProfile' => [
                'id' => $account->uuid,
                'name' => $account->username,
                'legacy' => false,
            ],
        ];

        if ($includeAvailableProfiles) {
            // Сами моянги ещё ничего не придумали с этими availableProfiles
            $availableProfiles[0] = $result['selectedProfile'];
            $result['availableProfiles'] = $availableProfiles;
        }

        return $result;
    }

}
