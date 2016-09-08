<?php
namespace common\components\SkinSystem;

use GuzzleHttp\Client as GuzzleClient;
use Yii;

class Api {

    const BASE_DOMAIN = 'http://skinsystem.ely.by';

    public function textures($username) : array {
        $response = $this->getClient()->get($this->getBuildUrl('/textures/' . $username));
        $textures = json_decode($response->getBody(), true);

        return $textures;
    }

    protected function getBuildUrl(string $url) : string {
        return self::BASE_DOMAIN . $url;
    }

    /**
     * @return GuzzleClient
     */
    protected function getClient() : GuzzleClient {
        return Yii::$app->guzzle;
    }

}
