<?php
namespace api\tests\unit\modules\oauth\models;

use api\modules\oauth\models\BaseOauthClientType;
use common\models\OauthClient;
use api\tests\unit\TestCase;

class BaseOauthClientTypeTest extends TestCase {

    public function testApplyTyClient(): void {
        $client = new OauthClient();

        /** @var BaseOauthClientType|\Mockery\MockInterface $form */
        $form = mock(BaseOauthClientType::class);
        $form->makePartial();
        $form->name = 'Application name';
        $form->websiteUrl = 'http://example.com';

        $form->applyToClient($client);
        $this->assertSame('Application name', $client->name);
        $this->assertSame('http://example.com', $client->website_url);
    }

}
