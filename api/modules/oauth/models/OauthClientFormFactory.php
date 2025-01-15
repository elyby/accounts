<?php
declare(strict_types=1);

namespace api\modules\oauth\models;

use api\modules\oauth\exceptions\UnsupportedOauthClientType;
use common\models\OauthClient;

final class OauthClientFormFactory {

    /**
     * @throws UnsupportedOauthClientType
     */
    public static function create(OauthClient $client): OauthClientTypeForm {
        return match ($client->type) {
            OauthClient::TYPE_WEB_APPLICATION => new WebApplicationType([
                'name' => $client->name,
                'websiteUrl' => $client->website_url,
                'description' => $client->description,
                'redirectUri' => $client->redirect_uri,
            ]),
            OauthClient::TYPE_DESKTOP_APPLICATION => new DesktopApplicationType([
                'name' => $client->name,
                'description' => $client->description,
                'websiteUrl' => $client->website_url,
            ]),
            OauthClient::TYPE_MINECRAFT_SERVER => new MinecraftServerType([
                'name' => $client->name,
                'websiteUrl' => $client->website_url,
                'minecraftServerIp' => $client->minecraft_server_ip,
            ]),
            // @phpstan-ignore match.unreachable (Not quite correct code, but the value comes from the user and might be not expected)
            default => throw new UnsupportedOauthClientType($client->type),
        };
    }

}
