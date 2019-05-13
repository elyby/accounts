<?php
declare(strict_types=1);

namespace common\components\SkinSystem;

use GuzzleHttp\ClientInterface;
use Yii;

// TODO: convert to complete Chrly client library
class Api {

    private const BASE_DOMAIN = 'http://skinsystem.ely.by';

    /** @var ClientInterface */
    private $client;

    /**
     * @param string $username
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * @return array
     */
    public function textures(string $username): ?array {
        $response = $this->getClient()->request('GET', $this->buildUrl('/textures/' . $username));
        if ($response->getStatusCode() !== 204) {
            return null;
        }

        return json_decode($response->getBody(), true);
    }

    public function setClient(ClientInterface $client): void {
        $this->client = $client;
    }

    private function buildUrl(string $url): string {
        return self::BASE_DOMAIN . $url;
    }

    private function getClient(): ClientInterface {
        if ($this->client === null) {
            $this->client = Yii::$container->get(ClientInterface::class);
        }

        return $this->client;
    }

}
