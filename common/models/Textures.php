<?php
namespace common\models;

use common\components\SkinSystem\Api as SkinSystemApi;
use DateInterval;
use DateTime;

class Textures {

    public $displayElyMark = true;

    /**
     * @var Account
     */
    protected $account;

    public function __construct(Account $account) {
        $this->account = $account;
    }

    public function getMinecraftResponse() {
        $response =  [
            'name' => $this->account->username,
            'id' => str_replace('-', '', $this->account->uuid),
            'properties' => [
                [
                    'name' => 'textures',
                    'signature' => 'Cg==',
                    'value' => $this->getTexturesValue(),
                ],
            ],
        ];

        if ($this->displayElyMark) {
            $response['ely'] = true;
        }

        return $response;
    }

    public function getTexturesValue($encrypted = true) {
        $array = [
            'timestamp' => (new DateTime())->add(new DateInterval('P2D'))->getTimestamp(),
            'profileId' => str_replace('-', '', $this->account->uuid),
            'profileName' => $this->account->username,
            'textures' => $this->getTextures(),
        ];

        if ($this->displayElyMark) {
            $array['ely'] = true;
        }

        if (!$encrypted) {
            return $array;
        } else {
            return static::encrypt($array);
        }
    }

    public function getTextures() {
        $api = new SkinSystemApi();
        return $api->textures($this->account->username);
    }

    public static function encrypt(array $data) {
        return base64_encode(stripcslashes(json_encode($data)));
    }

    public static function decrypt($string, $assoc = true) {
        return json_decode(base64_decode($string), $assoc);
    }

}
