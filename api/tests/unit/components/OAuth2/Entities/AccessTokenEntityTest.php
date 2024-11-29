<?php
declare(strict_types=1);

namespace api\tests\unit\components\OAuth2\Entities;

use api\components\OAuth2\Entities\AccessTokenEntity;
use api\tests\unit\TestCase;
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

        $token = $entity->toString();
        $payloads = json_decode(base64_decode(explode('.', $token)[1]), true);
        $this->assertSame('first second', $payloads['scope']);
    }

    private function createScopeEntity(string $id): ScopeEntityInterface {
        /** @var ScopeEntityInterface|\PHPUnit\Framework\MockObject\MockObject $entity */
        $entity = $this->createMock(ScopeEntityInterface::class);
        $entity->method('getIdentifier')->willReturn($id);

        return $entity;
    }

}
