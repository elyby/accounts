<?php
declare(strict_types=1);

namespace api\tests\unit\modules\oauth\models;

use api\modules\oauth\exceptions\UnsupportedOauthClientType;
use api\modules\oauth\models\DesktopApplicationType;
use api\modules\oauth\models\MinecraftServerType;
use api\modules\oauth\models\OauthClientFormFactory;
use api\modules\oauth\models\WebApplicationType;
use api\tests\unit\TestCase;
use common\models\OauthClient;

final class OauthClientFormFactoryTest extends TestCase {

    public function testCreateWebApplication(): void {
        $client = new OauthClient();
        $client->type = OauthClient::TYPE_WEB_APPLICATION;
        $client->name = 'Application name';
        $client->description = 'Application description.';
        $client->website_url = 'http://example.com';
        $client->redirect_uri = 'http://example.com/oauth/ely';
        /** @var WebApplicationType $requestForm */
        $requestForm = OauthClientFormFactory::create($client);
        $this->assertInstanceOf(WebApplicationType::class, $requestForm);
        $this->assertSame('Application name', $requestForm->name);
        $this->assertSame('Application description.', $requestForm->description);
        $this->assertSame('http://example.com', $requestForm->websiteUrl);
        $this->assertSame('http://example.com/oauth/ely', $requestForm->redirectUri);
    }

    public function testCreateDesktopApplication(): void {
        $client = new OauthClient();
        $client->type = OauthClient::TYPE_DESKTOP_APPLICATION;
        $client->name = 'Application name';
        $client->description = 'Application description.';
        $client->website_url = 'http://example.com';
        /** @var \api\modules\oauth\models\DesktopApplicationType $requestForm */
        $requestForm = OauthClientFormFactory::create($client);
        $this->assertInstanceOf(DesktopApplicationType::class, $requestForm);
        $this->assertSame('Application name', $requestForm->name);
        $this->assertSame('Application description.', $requestForm->description);
        $this->assertSame('http://example.com', $requestForm->websiteUrl);
    }

    public function testCreateMinecraftServer(): void {
        $client = new OauthClient();
        $client->type = OauthClient::TYPE_MINECRAFT_SERVER;
        $client->name = 'Server name';
        $client->website_url = 'http://example.com';
        $client->minecraft_server_ip = 'localhost:12345';
        /** @var MinecraftServerType $requestForm */
        $requestForm = OauthClientFormFactory::create($client);
        $this->assertInstanceOf(MinecraftServerType::class, $requestForm);
        $this->assertSame('Server name', $requestForm->name);
        $this->assertSame('http://example.com', $requestForm->websiteUrl);
        $this->assertSame('localhost:12345', $requestForm->minecraftServerIp);
    }

    public function testCreateUnknownType(): void {
        $this->expectException(UnsupportedOauthClientType::class);

        $client = new OauthClient();
        $client->type = 'unknown-type'; // @phpstan-ignore assign.propertyType (its alright for tests)
        OauthClientFormFactory::create($client);
    }

}
