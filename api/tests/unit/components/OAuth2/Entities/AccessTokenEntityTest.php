<?php
declare(strict_types=1);

namespace api\tests\unit\components\OAuth2\Entities;

use api\components\OAuth2\Entities\AccessTokenEntity;
use api\tests\unit\TestCase;
use DateInterval;
use DateTimeImmutable;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

class AccessTokenEntityTest extends TestCase {

    public function testToString() {
        /** @var ClientEntityInterface|\PHPUnit\Framework\MockObject\MockObject $client */
        $client = $this->createMock(ClientEntityInterface::class);
        $client->method('getIdentifier')->willReturn('mockClientId');

        $entity = new AccessTokenEntity();
        $entity->setClient($client);
        $entity->setExpiryDateTime(new DateTimeImmutable());
        $entity->addScope($this->createScopeEntity('first'));
        $entity->addScope($this->createScopeEntity('second'));
        $entity->addScope($this->createScopeEntity('offline_access'));

        $token = (string)$entity;
        $payloads = json_decode(base64_decode(explode('.', $token)[1]), true);
        $this->assertStringNotContainsString('offline_access', $payloads['ely-scopes']);

        $scopes = $entity->getScopes();
        $this->assertCount(3, $scopes);
        $this->assertSame('first', $scopes[0]->getIdentifier());
        $this->assertSame('second', $scopes[1]->getIdentifier());
        $this->assertSame('offline_access', $scopes[2]->getIdentifier());
    }

    public function testGetExpiryDateTime() {
        $initialExpiry = (new DateTimeImmutable())->add(new DateInterval('P1D'));

        $entity = new AccessTokenEntity();
        $entity->setExpiryDateTime($initialExpiry);
        $this->assertSame($initialExpiry, $entity->getExpiryDateTime());

        $entity = new AccessTokenEntity();
        $entity->setExpiryDateTime($initialExpiry);
        $entity->addScope($this->createScopeEntity('change_skin'));
        $this->assertEqualsWithDelta(time() + 60 * 60, $entity->getExpiryDateTime()->getTimestamp(), 5);

        $entity = new AccessTokenEntity();
        $entity->setExpiryDateTime($initialExpiry);
        $entity->addScope($this->createScopeEntity('obtain_account_email'));
        $this->assertEqualsWithDelta(time() + 60 * 60, $entity->getExpiryDateTime()->getTimestamp(), 5);
    }

    private function createScopeEntity(string $id): ScopeEntityInterface {
        /** @var ScopeEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(ScopeEntityInterface::class);
        $entity->method('getIdentifier')->willReturn($id);

        return $entity;
    }

}
