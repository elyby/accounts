<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use common\models\Account;
use Lcobucci\JWT\Token;

class AuthenticateData {

    /**
     * @var Account
     */
    private $account;

    /**
     * @var Token
     */
    private $accessToken;

    /**
     * @var string
     */
    private $clientToken;

    public function __construct(Account $account, string $accessToken, string $clientToken) {
        $this->account = $account;
        $this->accessToken = $accessToken;
        $this->clientToken = $clientToken;
    }

    public function getResponseData(bool $includeAvailableProfiles = false): array {
        $result = [
            'accessToken' => $this->accessToken,
            'clientToken' => $this->clientToken,
            'selectedProfile' => [
                'id' => $this->account->uuid,
                'name' => $this->account->username,
                'legacy' => false,
            ],
        ];

        if ($includeAvailableProfiles) {
            // The Mojang themselves haven't come up with anything yet with these availableProfiles
            $availableProfiles[0] = $result['selectedProfile'];
            $result['availableProfiles'] = $availableProfiles;
        }

        return $result;
    }

}
