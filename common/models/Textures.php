<?php
namespace common\models;

use common\components\SkinSystem\Api as SkinSystemApi;
use DateInterval;
use DateTime;
use GuzzleHttp\Exception\RequestException;
use Yii;

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
        $response = [
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
        }

        return static::encrypt($array);
    }

    public function getTextures(): array {
        try {
            $textures = $this->getSkinsystemApi()->textures($this->account->username);
        } catch (RequestException $e) {
            Yii::warning('Cannot get textures from skinsystem.ely.by. Exception message is ' . $e->getMessage());
            $textures = [
                'SKIN' => [
                    'url' => 'http://skins.minecraft.net/MinecraftSkins/' . $this->account->username . '.png',
                    'hash' => md5(uniqid('random, please', true)),
                ],
            ];
        }

        return $textures;
    }

    public static function encrypt(array $data) {
        return base64_encode(stripcslashes(json_encode($data)));
    }

    public static function decrypt($string, $assoc = true) {
        return json_decode(base64_decode($string), $assoc);
    }

    protected function getSkinsystemApi(): SkinSystemApi {
        return new SkinSystemApi();
    }

}
