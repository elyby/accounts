<?php
namespace common\components\Mojang;

use common\components\Mojang\exceptions\MojangApiException;
use common\components\Mojang\exceptions\NoContentException;
use common\components\Mojang\response\UsernameToUUIDResponse;
use GuzzleHttp\Client as GuzzleClient;

class Api {

    /**
     * @param string $username
     * @param int    $atTime
     *
     * @return UsernameToUUIDResponse
     * @throws MojangApiException
     * @throws NoContentException|\GuzzleHttp\Exception\RequestException
     * @url http://wiki.vg/Mojang_API#Username_-.3E_UUID_at_time
     */
    public function usernameToUUID($username, $atTime = null) {
        $query = [];
        if ($atTime !== null) {
            $query['atTime'] = $atTime;
        }

        $response = $this->createClient()->get($this->buildUsernameToUUIDRoute($username), $query);
        if ($response->getStatusCode() === 204) {
            throw new NoContentException('Username not found');
        } elseif ($response->getStatusCode() !== 200) {
            throw new MojangApiException('Unexpected request result');
        }

        $data = json_decode($response->getBody(), true);
        $responseObj = new UsernameToUUIDResponse();
        $responseObj->id = $data['id'];
        $responseObj->name = $data['name'];
        $responseObj->legacy = isset($data['legacy']);
        $responseObj->demo = isset($data['demo']);

        return $responseObj;
    }

    protected function createClient() {
        return new GuzzleClient();
    }

    protected function buildUsernameToUUIDRoute($username) {
        return 'https://api.mojang.com/users/profiles/minecraft/' . $username;
    }

}
