<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\modules\oauth\exceptions\UnsupportedOauthClientType;
use common\models\OauthClient;

class OauthClientFormFactory {

    /**
     * @param OauthClient $client
     *
     * @return OauthClientTypeForm
     * @throws UnsupportedOauthClientType
     */
    public static function create(OauthClient $client): OauthClientTypeForm {
        return match ($client->type) {
            OauthClient::TYPE_APPLICATION => new ApplicationType([
                'name' => $client->name,
                'websiteUrl' => $client->website_url,
                'description' => $client->description,
                'redirectUri' => $client->redirect_uri,
            ]),
            OauthClient::TYPE_MINECRAFT_SERVER => new MinecraftServerType([
                'name' => $client->name,
                'websiteUrl' => $client->website_url,
                'minecraftServerIp' => $client->minecraft_server_ip,
            ]),
            default => throw new UnsupportedOauthClientType($client->type),
        };
    }

}
