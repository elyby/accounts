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
        switch ($client->type) {
            case OauthClient::TYPE_APPLICATION:
                return new ApplicationType([
                    'name' => $client->name,
                    'websiteUrl' => $client->website_url,
                    'description' => $client->description,
                    'redirectUri' => $client->redirect_uri,
                ]);
            case OauthClient::TYPE_MINECRAFT_SERVER:
                return new MinecraftServerType([
                    'name' => $client->name,
                    'websiteUrl' => $client->website_url,
                    'minecraftServerIp' => $client->minecraft_server_ip,
                ]);
        }

        throw new UnsupportedOauthClientType($client->type);
    }

}
