<?php
declare(strict_types=1);

namespace api\modules\authserver\models;

use common\models\Account;

final readonly class AuthenticateData {

    public function __construct(
        private Account $account,
        private string $accessToken,
        private string $clientToken,
        private bool $requestUser,
    ) {
    }

    /**
     * @return array{
     *     accessToken: string,
     *     clientToken: string,
     *     selectedProfile: array{
     *         id: string,
     *         name: string,
     *     },
     *     availableProfiles?: array<array{
     *         id: string,
     *         name: string,
     *     }>,
     *     user?: array{
     *         id: string,
     *         username: string,
     *         properties: array<array{
     *             name: string,
     *             value: string,
     *         }>,
     *     },
     * }
     */
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
