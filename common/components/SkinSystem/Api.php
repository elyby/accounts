<?php
namespace common\components\SkinSystem;

use GuzzleHttp\Client as GuzzleClient;
use Yii;

class Api {

    const BASE_DOMAIN = 'http://skinsystem.ely.by';

    /**
     * @param string $username
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    public function textures(string $username): array {
        $response = $this->getClient()->get($this->getBuildUrl('/textures/' . $username));

        return json_decode($response->getBody(), true);
    }

    protected function getBuildUrl(string $url): string {
        return self::BASE_DOMAIN . $url;
    }

    /**
     * @return GuzzleClient
     */
    protected function getClient(): GuzzleClient {
        return Yii::$app->guzzle;
    }

}
