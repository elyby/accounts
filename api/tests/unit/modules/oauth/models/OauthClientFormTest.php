<?php
declare(strict_types=1);

namespace api\tests\unit\modules\oauth\models;

use api\modules\oauth\models\OauthClientForm;
use api\modules\oauth\models\OauthClientTypeForm;
use api\tests\unit\TestCase;
use common\models\OauthClient;
use common\tasks\ClearOauthSessions;

final class OauthClientFormTest extends TestCase {

    public function testSave(): void {
        $client = $this->createPartialMock(OauthClient::class, ['save']);
        $client->method('save')->willReturn(true);
        $client->account_id = 1;
        $client->type = OauthClient::TYPE_WEB_APPLICATION;
        $client->name = 'Test application';

        $form = $this->createPartialMock(OauthClientForm::class, ['getClient', 'isClientExists']);
        $form->method('getClient')->willReturn($client);
        $form->expects($this->exactly(3))->method('isClientExists')->willReturnOnConsecutiveCalls(true, true, false);

        $requestType = $this->createMock(OauthClientTypeForm::class);
        $requestType->expects($this->once())->method('validate')->willReturn(true);
        $requestType->expects($this->once())->method('applyToClient')->with($client);

        $this->assertTrue($form->save($requestType));
        $this->assertSame('test-application2', $client->id);
        $this->assertNotNull($client->secret);
        $this->assertSame(64, mb_strlen($client->secret));
    }

    public function testSaveUpdateExistsModel(): void {
        $client = $this->createPartialMock(OauthClient::class, ['save']);
        $client->method('save')->willReturn(true);
        $client->setIsNewRecord(false);
        $client->id = 'application-id';
        $client->secret = 'application_secret';
        $client->account_id = 1;
        $client->type = OauthClient::TYPE_WEB_APPLICATION;
        $client->name = 'Application name';
        $client->description = 'Application description';
        $client->redirect_uri = 'http://example.com/oauth/ely';
        $client->website_url = 'http://example.com';

        $form = $this->createPartialMock(OauthClientForm::class, ['getClient', 'isClientExists']);
        $form->method('getClient')->willReturn($client);
        $form->method('isClientExists')->willReturn(false);

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

    public function testDelete(): void {
        $client = $this->createPartialMock(OauthClient::class, ['save']);
        $client->method('save')->willReturn(true);
        $client->id = 'mocked-id';
        $client->type = OauthClient::TYPE_WEB_APPLICATION;

        $form = new OauthClientForm($client);
        $this->assertTrue($form->delete());
        $this->assertTrue($form->getClient()->is_deleted);
        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('mocked-id', $job->clientId);
        $this->assertNull($job->notSince);
    }

    public function testReset(): void {
        $client = $this->createPartialMock(OauthClient::class, ['save']);
        $client->method('save')->willReturn(true);
        $client->id = 'mocked-id';
        $client->secret = 'initial_secret';
        $client->type = OauthClient::TYPE_WEB_APPLICATION;

        $form = new OauthClientForm($client);
        $this->assertTrue($form->reset());
        $this->assertSame('initial_secret', $form->getClient()->secret);
        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('mocked-id', $job->clientId);
        $this->assertEqualsWithDelta(time(), $job->notSince, 2);
    }

    public function testResetWithSecret(): void {
        $client = $this->createPartialMock(OauthClient::class, ['save']);
        $client->method('save')->willReturn(true);
        $client->id = 'mocked-id';
        $client->secret = 'initial_secret';
        $client->type = OauthClient::TYPE_WEB_APPLICATION;

        $form = new OauthClientForm($client);
        $this->assertTrue($form->reset(true));
        $this->assertNotSame('initial_secret', $form->getClient()->secret);
        /** @var ClearOauthSessions $job */
        $job = $this->tester->grabLastQueuedJob();
        $this->assertInstanceOf(ClearOauthSessions::class, $job);
        $this->assertSame('mocked-id', $job->clientId);
        $this->assertEqualsWithDelta(time(), $job->notSince, 2);
    }

}
