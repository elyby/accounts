<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use common\models\Account;

final class AuthenticateData {

    private Account $account;

    private string $accessToken;

    private string $clientToken;

    private bool $requestUser;

    public function __construct(Account $account, string $accessToken, string $clientToken, bool $requestUser) {
        $this->account = $account;
        $this->accessToken = $accessToken;
        $this->clientToken = $clientToken;
        $this->requestUser = $requestUser;
    }

    public function getResponseData(bool $includeAvailableProfiles = false): array {
        $uuid = str_replace('-', '', $this->account->uuid);
        $result = [
            'accessToken' => $this->accessToken,
            'clientToken' => $this->clientToken,
            'selectedProfile' => [
                // Might contain a lot more fields, but even Mojang returns only those:
                'id' => $uuid,
                'name' => $this->account->username,
            ],
        ];

        if ($includeAvailableProfiles) {
            // The Mojang themselves haven't come up with anything yet with these availableProfiles
            $availableProfiles[0] = $result['selectedProfile'];
            $result['availableProfiles'] = $availableProfiles;
        }

        if ($this->requestUser) {
            // There are a lot of fields, but even Mojang returns only those:
            $result['user'] = [
                'id' => $uuid,
                'username' => $this->account->username,
                'properties' => [
                    [
                        'name' => 'preferredLanguage',
                        'value' => $this->account->lang,
                    ],
                ],
            ];
        }

        return $result;
    }

}
