<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use common\helpers\Error as E;
use common\models\OauthClient;
use common\validators\MinecraftServerAddressValidator;
use yii\helpers\ArrayHelper;

class MinecraftServerType extends BaseOauthClientType {

    public $minecraftServerIp;

    public function rules(): array {
        return ArrayHelper::merge(parent::rules(), [
            ['minecraftServerIp', MinecraftServerAddressValidator::class, 'message' => E::MINECRAFT_SERVER_IP_INVALID],
        ]);
    }

    public function applyToClient(OauthClient $client): void {
        parent::applyToClient($client);
        $client->minecraft_server_ip = $this->minecraftServerIp;
    }

}
