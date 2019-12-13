<?php
declare(strict_types=1);

namespace api\tests\unit\modules\oauth\models;

use api\modules\oauth\models\BaseOauthClientType;
use api\tests\unit\TestCase;
use common\models\OauthClient;

class BaseOauthClientTypeTest extends TestCase {

    public function testApplyTyClient(): void {
        $client = new OauthClient();

        $form = $this->getMockForAbstractClass(BaseOauthClientType::class);
        $form->name = 'Application name';
        $form->websiteUrl = 'http://example.com';

        $form->applyToClient($client);
        $this->assertSame('Application name', $client->name);
        $this->assertSame('http://example.com', $client->website_url);
    }

}
