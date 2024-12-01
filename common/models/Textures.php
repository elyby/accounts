<?php
declare(strict_types=1);

namespace common\models;

use common\components\SkinsSystemApi;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Yii;

class Textures {

    private const MAX_RETRIES = 3;

    public function __construct(protected Account $account) {
    }

    public function getMinecraftResponse(bool $signed = false): array {
        $uuid = str_replace('-', '', $this->account->uuid);
        $profile = $this->getProfile($uuid, $signed);
        if ($profile['id'] !== $uuid) {
            // Also a case that shouldn't happen, but is technically possible
            $profile['id'] = $uuid;
        }

        return $profile;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getProfile(string $uuid, bool $signed): array {
        /** @var SkinsSystemApi $api */
        $api = Yii::$container->get(SkinsSystemApi::class);
        if (YII_ENV_PROD) {
            $api->setClient(new GuzzleHttpClient([
                'connect_timeout' => 2,
                'read_timeout' => 7,
            ]));
        }

        // It will be better to handle retries with Guzzle middleware, but for speed I'll do it in place
        $lastException = null;
        for ($i = 0; $i < self::MAX_RETRIES; $i++) {
            try {
                return $api->profile($this->account->username, $signed, $uuid);
            } catch (GuzzleException $e) {
                $lastException = $e;
                sleep(1);
            }
        }

        Yii::warning('Cannot get textures from skinsystem.ely.by. Exception message is ' . $lastException->getMessage());

        throw $lastException;
    }

}
