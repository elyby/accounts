<?php
declare(strict_types=1);

namespace common\models;

use common\components\SkinsSystemApi as SkinSystemApi;
use DateInterval;
use DateTime;
use GuzzleHttp\Exception\GuzzleException;
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

    public function getMinecraftResponse(): array {
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
        /** @var SkinSystemApi $api */
        $api = Yii::$container->get(SkinSystemApi::class);
        if (YII_ENV_PROD) {
            $api->setClient(new \GuzzleHttp\Client([
                'connect_timeout' => 2,
                'decode_content' => false,
                'read_timeout' => 5,
                'stream' => true,
                'timeout' => 5,
            ]));
        }

        try {
            $textures = $api->textures($this->account->username);
        } catch (RequestException $e) {
            Yii::warning('Cannot get textures from skinsystem.ely.by. Exception message is ' . $e->getMessage());
        } catch (GuzzleException $e) {
            Yii::warning($e);
        }

        return $textures ?? [];
    }

    public static function encrypt(array $data): string {
        return base64_encode(stripcslashes(json_encode($data)));
    }

    public static function decrypt($string, $assoc = true) {
        return json_decode(base64_decode($string), $assoc);
    }

}
