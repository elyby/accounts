<?php
namespace tests\codeception\api\unit\modules\oauth\models;

use api\modules\oauth\models\OauthClientForm;
use api\modules\oauth\models\OauthClientTypeForm;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;
use tests\codeception\api\unit\TestCase;

class OauthClientFormTest extends TestCase {

    public function testSave() {
        /** @var OauthClient|\Mockery\MockInterface $client */
        $client = mock(OauthClient::class . '[save]');
        $client->shouldReceive('save')->andReturn(true);
        $client->account_id = 1;
        $client->type = OauthClient::TYPE_APPLICATION;
        $client->name = 'Test application';

        /** @var OauthClientForm|\Mockery\MockInterface $form */
        $form = mock(OauthClientForm::class . '[isClientExists]', [$client]);
        $form->shouldAllowMockingProtectedMethods();
        $form->shouldReceive('isClientExists')
            ->times(3)
            ->andReturnValues([true, true, false]);

        /** @var OauthClientTypeForm|\Mockery\MockInterface $requestType */
        $requestType = mock(OauthClientTypeForm::class);
        $requestType->shouldReceive('validate')->once()->andReturn(true);
        $requestType->shouldReceive('applyToClient')->once()->withArgs([$client]);

        $this->assertTrue($form->save($requestType));
        $this->assertSame('test-application2', $client->id);
        $this->assertNotNull($client->secret);
        $this->assertSame(64, mb_strlen($client->secret));
    }

    public function testSaveUpdateExistsModel() {
        /** @var OauthClient|\Mockery\MockInterface $client */
        $client = mock(OauthClient::class . '[save]');
        $client->shouldReceive('save')->andReturn(true);
        $client->setIsNewRecord(false);
        $client->id = 'application-id';
        $client->secret = 'application_secret';
        $client->account_id = 1;
        $client->type = OauthClient::TYPE_APPLICATION;
        $client->name = 'Application name';
        $client->description = 'Application description';
        $client->redirect_uri = 'http://example.com/oauth/ely';
        $client->website_url = 'http://example.com';

        /** @var OauthClientForm|\Mockery\MockInterface $form */
        $form = mock(OauthClientForm::class . '[isClientExists]', [$client]);
        $form->shouldAllowMockingProtectedMethods();
        $form->shouldReceive('isClientExists')->andReturn(false);

        $request = new class implements OauthClientTypeForm {
            public function load($data): bool {
                return true;
            }

            public function validate(): bool {
                return true;
            }

            public function getValidationErrors(): array {
                return [];
            }

            public function applyToClient(OauthClient $client): void {
                $client->name = 'New name';
                $client->description = 'New description.';
            }
        };

        $this->assertTrue($form->save($request));
        $this->assertSame('application-id', $client->id);
        $this->assertSame('application_secret', $client->secret);
        $this->assertSame('New name', $client->name);
        $this->assertSame('New description.', $client->description);
        $this->assertSame('http://example.com/oauth/ely', $client->redirect_uri);
        $this->assertSame('http://example.com', $client->website_url);
    }

    public function testDelete() {
        /** @var OauthClient|\Mockery\MockInterface $client */
        $client = mock(OauthClient::class . '[save]');
        $client->id = 'mocked-id';
        $client->type = OauthClient::TYPE_APPLICATION;
        $client->shouldReceive('save')->andReturn(true);

        $form = new OauthClientForm($client);
        $this->assertTrue($form->delete());
        $this->assertTrue($form->getClient()->is_deleted);
        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('mocked-id', $job->clientId);
        $this->assertNull($job->notSince);
    }

    public function testReset() {
        /** @var OauthClient|\Mockery\MockInterface $client */
        $client = mock(OauthClient::class . '[save]');
        $client->id = 'mocked-id';
        $client->secret = 'initial_secret';
        $client->type = OauthClient::TYPE_APPLICATION;
        $client->shouldReceive('save')->andReturn(true);

        $form = new OauthClientForm($client);
        $this->assertTrue($form->reset());
        $this->assertSame('initial_secret', $form->getClient()->secret);
        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('mocked-id', $job->clientId);
        $this->assertEquals(time(), $job->notSince, '', 2);
    }

    public function testResetWithSecret() {
        /** @var OauthClient|\Mockery\MockInterface $client */
        $client = mock(OauthClient::class . '[save]');
        $client->id = 'mocked-id';
        $client->secret = 'initial_secret';
        $client->type = OauthClient::TYPE_APPLICATION;
        $client->shouldReceive('save')->andReturn(true);

        $form = new OauthClientForm($client);
        $this->assertTrue($form->reset(true));
        $this->assertNotSame('initial_secret', $form->getClient()->secret);
        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('mocked-id', $job->clientId);
        $this->assertEquals(time(), $job->notSince, '', 2);
    }

}
