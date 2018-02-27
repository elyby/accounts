<?php
namespace tests\codeception\api\unit\modules\oauth\models;

use api\modules\oauth\models\ApplicationType;
use common\models\OauthClient;
use tests\codeception\api\unit\TestCase;

class ApplicationTypeTest extends TestCase {

    public function testApplyToClient(): void {
        $model = new ApplicationType();
        $model->name = 'Application name';
        $model->websiteUrl = 'http://example.com';
        $model->redirectUri = 'http://example.com/oauth/ely';
        $model->description = 'Application description.';

        $client = new OauthClient();

        $model->applyToClient($client);

        $this->assertSame('Application name', $client->name);
        $this->assertSame('Application description.', $client->description);
        $this->assertSame('http://example.com/oauth/ely', $client->redirect_uri);
        $this->assertSame('http://example.com', $client->website_url);
    }

}
