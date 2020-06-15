<?php
declare(strict_types=1);

namespace common\tests\unit\tasks;

use common\models\OauthClient;
use common\models\OauthSession;
use common\tasks\ClearOauthSessions;
use common\tests\fixtures;
use common\tests\unit\TestCase;
use yii\queue\Queue;

class ClearOauthSessionsTest extends TestCase {

    public function _fixtures(): array {
        return [
            'oauthClients' => fixtures\OauthClientFixture::class,
            'oauthSessions' => fixtures\OauthSessionFixture::class,
        ];
    }

    public function testCreateFromClient() {
        $client = new OauthClient();
        $client->id = 'mocked-id';

        $result = ClearOauthSessions::createFromOauthClient($client);
        $this->assertInstanceOf(ClearOauthSessions::class, $result);
        $this->assertSame('mocked-id', $result->clientId);
        $this->assertNull($result->notSince);

        $result = ClearOauthSessions::createFromOauthClient($client, time());
        $this->assertInstanceOf(ClearOauthSessions::class, $result);
        $this->assertSame('mocked-id', $result->clientId);
        $this->assertEqualsWithDelta(time(), $result->notSince, 1);
    }

    public function testExecute() {
        $task = new ClearOauthSessions('deleted-oauth-client-with-sessions', 1519510065);
        $task->execute($this->createMock(Queue::class));

        $this->assertFalse(OauthSession::find()->andWhere(['legacy_id' => 3])->exists());
        $this->assertTrue(OauthSession::find()->andWhere(['legacy_id' => 4])->exists());

        $task = new ClearOauthSessions('deleted-oauth-client-with-sessions');
        $task->execute($this->createMock(Queue::class));

        $this->assertFalse(OauthSession::find()->andWhere(['legacy_id' => 4])->exists());

        $task = new ClearOauthSessions('some-not-exists-client-id');
        $task->execute($this->createMock(Queue::class));
    }

}
