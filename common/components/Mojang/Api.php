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
     * @throws NoContentException
     * @url http://wiki.vg/Mojang_API#Username_-.3E_UUID_at_time
     */
    public function usernameToUUID($username, $atTime = null) {
        $client = $this->createClient();
        $request = $client->createRequest('GET', 'https://api.mojang.com/users/profiles/minecraft/' . $username);
        $queryParams = [];
        if ($atTime !== null) {
            $queryParams['atTime'] = $atTime;
        }

        $request->setQuery($queryParams);
        $response = $client->send($request);
        if ($response->getStatusCode() === 204) {
            throw new NoContentException('Username not found');
        } elseif ($response->getStatusCode() !== 200) {
            throw new MojangApiException('Unexpected request result');
        }

        $data = $response->json(['object' => false]);
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

}
