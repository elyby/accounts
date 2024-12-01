<?php
namespace common\tests\unit\models;

use common\models\OauthClient;
use common\tests\fixtures\OauthClientFixture;
use common\tests\unit\TestCase;

class OauthClientQueryTest extends TestCase {

    public function _fixtures(): array {
        return [
            'oauthClients' => OauthClientFixture::class,
        ];
    }

    public function testDefaultHideDeletedEntries(): void {
        /** @var OauthClient[] $clients */
        $clients = OauthClient::find()->all();
        $this->assertEmpty(array_filter($clients, fn(OauthClient $client): bool => (bool)$client->is_deleted === true));
        $this->assertNull(OauthClient::findOne('deleted-oauth-client'));
    }

    public function testAllowFindDeletedEntries(): void {
        /** @var OauthClient[] $clients */
        $clients = OauthClient::find()->includeDeleted()->all();
        $this->assertNotEmpty(array_filter($clients, fn(OauthClient $client): bool => (bool)$client->is_deleted === true));
        $client = OauthClient::find()
            ->includeDeleted()
            ->andWhere(['id' => 'deleted-oauth-client'])
            ->one();
        $this->assertInstanceOf(OauthClient::class, $client);
        $deletedClients = OauthClient::find()->onlyDeleted()->all();
        $this->assertEmpty(array_filter($deletedClients, fn(OauthClient $client): bool => (bool)$client->is_deleted === false));
    }

}
