<?php
declare(strict_types=1);

namespace common\models;

use ArrayObject;
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
            // This case shouldn't happen at all, but synchronization isn't perfect and sometimes
            // information might be not updated. Provide fallback solution
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
                            'textures' => new ArrayObject(), // Force {} rather than [] when json_encode
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
            $profile['id'] = $uuid;
        }

        if ($signed) {
            // This is a completely impossible case. But the most impossible things happen most of the time.
            // We have received complaints that sometimes an empty value comes in the signature field.
            // This code is an attempt at an investigation. If no such cases are reported for the foreseeable future,
            // then this code can be removed
            foreach ($profile['properties'] as &$property) {
                if ($property['name'] === 'textures') {
                    if (!isset($property['signature'])) {
                        Yii::warning('Signature was required, but field was not returned from the skinsystem\'s server');
                        $property['signature'] = 'Cg==';
                    } elseif (empty($property['signature'])) {
                        Yii::warning('Signature was required, but contains an empty value from skinsystem\'s server');
                        $property['signature'] = 'Cg==';
                    }
                }
            }
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
