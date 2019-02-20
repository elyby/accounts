<?php
namespace api\tests\unit\modules\oauth\models;

use api\modules\oauth\models\MinecraftServerType;
use common\models\OauthClient;
use api\tests\unit\TestCase;

class MinecraftServerTypeTest extends TestCase {

    public function testApplyToClient(): void {
        $model = new MinecraftServerType();
        $model->name = 'Server name';
        $model->websiteUrl = 'http://example.com';
        $model->minecraftServerIp = 'localhost:12345';

        $client = new OauthClient();

        $model->applyToClient($client);

        $this->assertSame('Server name', $client->name);
        $this->assertSame('http://example.com', $client->website_url);
        $this->assertSame('localhost:12345', $client->minecraft_server_ip);
    }

}
