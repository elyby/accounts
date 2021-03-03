<?php
declare(strict_types=1);

namespace common\models;

use Carbon\Carbon;
use common\components\SkinsSystemApi;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Yii;

class Textures {

    protected Account $account;

    public function __construct(Account $account) {
        $this->account = $account;
    }

    public function getMinecraftResponse(bool $signed = false): array {
        $uuid = str_replace('-', '', $this->account->uuid);
        $profile = $this->getProfile($signed);
        if ($profile === null) {
            // This case shouldn't happen at all, but until we find out how it'll actually behave,
            // provide for a fallback solution
            Yii::warning("By some reasons there is no profile for \"{$this->account->username}\".");

            $profile = [
                'name' => $this->account->username,
                'id' => $uuid,
                'properties' => [
                    [
                        'name' => 'textures',
                        'value' => base64_encode(json_encode([
                            'timestamp' => Carbon::now()->getPreciseTimestamp(3),
                            'profileId' => $uuid,
                            'profileName' => $this->account->username,
                            'textures' => [],
                        ])),
                    ],
                    [
                        'name' => 'ely',
                        'value' => 'but why are you asking?',
                    ],
                ],
            ];

            if ($signed) {
                // I don't remember why this value has been used, but it was, so keep the same behavior until
                // figure out why it was made in this way
                $profile['properties'][0]['signature'] = 'Cg==';
            }
        } elseif ($profile['id'] !== $uuid) {
            // Also a case that shouldn't happen, but is technically possible
            Yii::warning("By an unknown reason username \"{$this->account->username}\" has an invalid id from chrly");
            $profile['id'] = $uuid;
        }

        return $profile;
    }

    private function getProfile(bool $signed): ?array {
        /** @var SkinsSystemApi $api */
        $api = Yii::$container->get(SkinsSystemApi::class);
        if (YII_ENV_PROD) {
            $api->setClient(new GuzzleHttpClient([
                'connect_timeout' => 2,
                'read_timeout' => 7,
            ]));
        }

        try {
            return $api->profile($this->account->username, $signed);
        } catch (GuzzleException $e) {
            Yii::warning('Cannot get textures from skinsystem.ely.by. Exception message is ' . $e->getMessage());
        }

        return null;
    }

}
